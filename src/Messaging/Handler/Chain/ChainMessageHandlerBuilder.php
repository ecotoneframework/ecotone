<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Chain;

use Ecotone\Messaging\Channel\DirectChannel;
use Ecotone\Messaging\Config\Container\ChannelReference;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ramsey\Uuid\Uuid;

/**
 * Class ChainMessageHandlerBuilder
 * @package Ecotone\Messaging\Handler\Chain
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class ChainMessageHandlerBuilder extends InputOutputMessageHandlerBuilder
{
    /**
     * @var MessageHandlerBuilderWithOutputChannel[]
     */
    private array $chainedMessageHandlerBuilders;
    private ?MessageHandlerBuilder $outputMessageHandler = null;

    private ?int $interceptedHandlerOffset = null;

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public function chainInterceptedHandler(MessageHandlerBuilderWithOutputChannel $messageHandler): self
    {
        Assert::null($this->interceptedHandlerOffset, "Cannot register two intercepted handlers {$messageHandler}. Already have {$this->interceptedHandlerOffset}");

        $this->chain($messageHandler);
        $this->interceptedHandlerOffset = array_key_last($this->chainedMessageHandlerBuilders);
        $this->requiredInterceptorReferenceNames = $messageHandler->getRequiredInterceptorNames();

        return $this;
    }

    public function chain(MessageHandlerBuilderWithOutputChannel $messageHandler): self
    {
        $outputChannelToKeep = $messageHandler->getOutputMessageChannelName();
        $messageHandler = $messageHandler
            ->withInputChannelName('')
            ->withOutputMessageChannel('');

        if ($outputChannelToKeep) {
            $messageHandler = ChainMessageHandlerBuilder::create()
                ->chainInterceptedHandler($messageHandler)
                ->chain(new OutputChannelKeeperBuilder($outputChannelToKeep));
        }

        $this->chainedMessageHandlerBuilders[] = $messageHandler;

        return $this;
    }

    /**
     * Do not combine with outputMessageChannel. Output message handler can be router and should contain output channel by his own
     *
     * @param MessageHandlerBuilder $outputMessageHandler
     * @return ChainMessageHandlerBuilder
     */
    public function withOutputMessageHandler(MessageHandlerBuilder $outputMessageHandler): self
    {
        $this->outputMessageHandler = $outputMessageHandler;

        return $this;
    }

    public function compile(MessagingContainerBuilder $builder): Definition
    {
        if ($this->outputMessageHandler && $this->outputMessageChannelName) {
            throw InvalidArgumentException::create("Can't configure output message handler and output message channel for chain handler");
        }

        if (count($this->chainedMessageHandlerBuilders) === 1 && ! $this->outputMessageHandler) {
            $singleHandler = $this->chainedMessageHandlerBuilders[0]
                ->withOutputMessageChannel($this->getOutputMessageChannelName());

            foreach ($this->orderedAroundInterceptors as $aroundInterceptorReference) {
                $singleHandler = $singleHandler->addAroundInterceptor($aroundInterceptorReference);
            }
            return $singleHandler->compile($builder);
        }

        $messageHandlersToChain = $this->chainedMessageHandlerBuilders;

        if ($this->outputMessageHandler) {
            $messageHandlersToChain[] = $this->outputMessageHandler;
        }

        $baseKey = Uuid::uuid4()->toString();
        foreach ($messageHandlersToChain as $key => $messageHandlerBuilder) {
            $nextHandlerKey = ($key + 1);
            $currentChannelName = $this->inputMessageChannelName . '_chain.' . $baseKey . $key;
            if ($key === $this->interceptedHandlerOffset) {
                foreach ($this->orderedAroundInterceptors as $aroundInterceptorReference) {
                    $messageHandlerBuilder = $messageHandlerBuilder->addAroundInterceptor($aroundInterceptorReference);
                }
            }
            if ($this->hasNextHandler($messageHandlersToChain, $nextHandlerKey)) {
                $messageHandlerBuilder = $messageHandlerBuilder->withOutputMessageChannel($this->inputMessageChannelName . '_chain.' . $baseKey . $nextHandlerKey);
            }
            $messageHandlerReference = $messageHandlerBuilder->compile($builder);
            if (! $messageHandlerReference) {
                // Cant compile
                throw InvalidArgumentException::create("Can't compile {$messageHandlerBuilder}");
            }
            $builder->register(new ChannelReference($currentChannelName), new Definition(DirectChannel::class, [
                $currentChannelName,
                $messageHandlerReference,
            ]));
        }

        $chainForwardPublisherReference = new Definition(ChainForwardPublisher::class, [
            new ChannelReference($this->inputMessageChannelName . '_chain.' . $baseKey . '0'),
            (bool)$this->outputMessageChannelName,
        ]);

        $serviceActivator = ServiceActivatorBuilder::createWithDefinition($chainForwardPublisherReference, 'forward')
            ->withOutputMessageChannel($this->outputMessageChannelName);

        if (is_null($this->interceptedHandlerOffset)) {
            foreach ($this->orderedAroundInterceptors as $aroundInterceptorReference) {
                $serviceActivator = $serviceActivator->addAroundInterceptor($aroundInterceptorReference);
            }
        }

        return $serviceActivator->compile($builder);
    }

    /**
     * @param array $messageHandlersToChain
     * @param $nextHandlerKey
     * @return bool
     */
    private function hasNextHandler(array $messageHandlersToChain, $nextHandlerKey): bool
    {
        return isset($messageHandlersToChain[$nextHandlerKey]);
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        if (! is_null($this->interceptedHandlerOffset)) {
            return $this->chainedMessageHandlerBuilders[$this->interceptedHandlerOffset]->getInterceptedInterface($interfaceToCallRegistry);
        }

        return $interfaceToCallRegistry->getFor(ChainForwardPublisher::class, 'forward');
    }
}
