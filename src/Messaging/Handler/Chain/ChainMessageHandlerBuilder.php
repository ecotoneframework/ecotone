<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Chain;

use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\Messaging\Channel\DirectChannel;
use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\Messaging\Config\NamedMessageChannel;
use SimplyCodedSoftware\Messaging\Config\ReferenceTypeFromNameResolver;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\PassThroughGateway;
use SimplyCodedSoftware\Messaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCallRegistry;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\RequestReplyProducer;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\Messaging\MessageHandler;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class ChainMessageHandlerBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Chain
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ChainMessageHandlerBuilder extends InputOutputMessageHandlerBuilder
{
    /**
     * @var MessageHandlerBuilderWithOutputChannel[]
     */
    private $chainedMessageHandlerBuilders;
    /**
     * @var string[]
     */
    private $requiredReferences = [];
    /**
     * @var MessageHandlerBuilder|null
     */
    private $outputMessageHandler;

    /**
     * ChainMessageHandlerBuilder constructor.
     */
    private function __construct()
    {
    }

    /**
     * @return ChainMessageHandlerBuilder
     */
    public static function create() : self
    {
        return new self();
    }

    /**
     * @param MessageHandlerBuilderWithOutputChannel $messageHandler
     * @return ChainMessageHandlerBuilder
     */
    public function chain(MessageHandlerBuilderWithOutputChannel $messageHandler) : self
    {
        $messageHandler
            ->withInputChannelName("")
            ->withOutputMessageChannel("");

        $this->chainedMessageHandlerBuilders[] = $messageHandler;
        foreach ($messageHandler->getRequiredReferenceNames() as $referenceName) {
            $this->requiredReferences[] = $referenceName;
        }

        $this->requiredReferences = array_unique($this->requiredReferences);
        return $this;
    }

    /**
     * Do not combine with outputMessageChannel. Output message handler can be router and should contain output channel by his own
     *
     * @param MessageHandlerBuilder $outputMessageHandler
     * @return ChainMessageHandlerBuilder
     */
    public function withOutputMessageHandler(MessageHandlerBuilder $outputMessageHandler) : self
    {
        $this->outputMessageHandler = $outputMessageHandler;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        if ($this->outputMessageHandler && $this->outputMessageChannelName) {
            throw InvalidArgumentException::create("Can't configure output message handler and output message channel for chain handler");
        }

        /** @var DirectChannel[] $bridgeChannels */
        $bridgeChannels = [];
        $messageHandlersToChain = $this->chainedMessageHandlerBuilders;

        if ($this->outputMessageHandler) {
            $messageHandlersToChain[] = $this->outputMessageHandler;
        }

        $baseKey = Uuid::uuid4()->toString();
        for ($key = 1; $key < count($messageHandlersToChain); $key++) {
            $bridgeChannels[$baseKey . $key] = DirectChannel::create();
        }
        $requestChannelName = $baseKey;
        $requestChannel = DirectChannel::create();
        $bridgeChannels[$baseKey] = $requestChannel;

        $customChannelResolver = InMemoryChannelResolver::createWithChanneResolver($channelResolver, $bridgeChannels);

        for ($key = 0; $key < count($messageHandlersToChain); $key++) {
            $currentKey = $baseKey . $key;
            $messageHandlerBuilder = $messageHandlersToChain[$key];
            $nextHandlerKey = ($key + 1);
            $previousHandlerKey = ($key - 1);

            if ($this->hasNextHandler($messageHandlersToChain, $nextHandlerKey)) {
                $messageHandlerBuilder->withOutputMessageChannel($baseKey . $nextHandlerKey);
            }

            $messageHandler = $messageHandlerBuilder->build($customChannelResolver, $referenceSearchService);

            if ($this->hasPreviousHandler($messageHandlersToChain, $previousHandlerKey)) {
                $customChannelResolver->resolve($currentKey)->subscribe($messageHandler);
            }

            if ($key === 0) {
                $requestChannel->subscribe($messageHandler);
            }
        }

        /** @var ChainGateway $gateway */
        $gateway = GatewayProxyBuilder::create(Uuid::uuid4()->toString(), ChainGateway::class, "execute", $requestChannelName)
                    ->build($referenceSearchService, $customChannelResolver);

        $serviceActivator = ServiceActivatorBuilder::createWithDirectReference(new ChainForwarder($gateway), "forward")
            ->withOutputMessageChannel($this->outputMessageChannelName);

        foreach ($this->orderedAroundInterceptors as $aroundInterceptorReference) {
            $serviceActivator->addAroundInterceptor($aroundInterceptorReference);
        }

        return $serviceActivator->build($channelResolver, $referenceSearchService);
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor(ChainForwarder::class, "forward");
    }


    /**
     * @inheritDoc
     */
    public function resolveRelatedReferences(InterfaceToCallRegistry $interfaceToCallRegistry) : iterable
    {
        $relatedReferences = [];
        if ($this->outputMessageHandler) {
            $relatedReferences[] = $this->outputMessageHandler->resolveRelatedReferences($interfaceToCallRegistry);
        }

        foreach ($this->chainedMessageHandlerBuilders as $chainedMessageHandlerBuilder) {
            foreach ($chainedMessageHandlerBuilder->resolveRelatedReferences($interfaceToCallRegistry) as $resolveRelatedReference) {
                $relatedReferences[] = $resolveRelatedReference;
            }
        }

        return $relatedReferences;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return $this->requiredReferences;
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
     * @param array $messageHandlersToChain
     * @param $previousHandlerKey
     * @return bool
     */
    private function hasPreviousHandler(array $messageHandlersToChain, $previousHandlerKey): bool
    {
        return isset($messageHandlersToChain[$previousHandlerKey]);
    }
}