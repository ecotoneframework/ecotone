<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Splitter;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\RequestReplyProducer;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class SplitterBuilder
 * @package Ecotone\Messaging\Handler\Splitter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SplitterBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithParameterConverters, MessageHandlerBuilderWithOutputChannel
{
    private string $referenceName;
    private array $methodParameterConverterBuilders = [];
    /**
     * @var string[]
     */
    private array $requiredReferenceNames = [];
    private ?object $directObject = null;

    private function __construct(string $referenceName, private string|InterfaceToCall $methodNameOrInterface)
    {
        $this->referenceName = $referenceName;

        if ($referenceName) {
            $this->requiredReferenceNames[] = $referenceName;
        }
    }

    public static function create(string $referenceName, InterfaceToCall $interfaceToCall): self
    {
        return new self($referenceName, $interfaceToCall);
    }

    /**
     * @inheritDoc
     */
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return [
            $this->methodNameOrInterface instanceof InterfaceToCall
                ? $this->methodNameOrInterface
                : $interfaceToCallRegistry->getFor($this->directObject, $this->methodNameOrInterface)
        ];
    }

    public static function createMessagePayloadSplitter(): self
    {
        return self::createWithDirectObject(new DirectMessageSplitter(), 'split');
    }

    public static function createWithDirectObject(object $directReferenceObject, string $methodName): self
    {
        $splitterBuilder = new self('', $methodName);
        $splitterBuilder->setDirectObject($directReferenceObject);

        return $splitterBuilder;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return $this->requiredReferenceNames;
    }

    /**
     * @inheritDoc
     */
    public function getParameterConverters(): array
    {
        return $this->methodParameterConverterBuilders;
    }

    /**
     * @inheritDoc
     */
    public function withMethodParameterConverters(array $methodParameterConverterBuilders): self
    {
        Assert::allInstanceOfType($methodParameterConverterBuilders, ParameterConverterBuilder::class);

        $this->methodParameterConverterBuilders = $methodParameterConverterBuilders;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $this->methodNameOrInterface instanceof InterfaceToCall
            ? $this->methodNameOrInterface
            : $interfaceToCallRegistry->getFor($this->directObject, $this->methodNameOrInterface);
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        $objectToInvokeOn = $this->directObject ? $this->directObject : $referenceSearchService->get($this->referenceName);
        $interfaceToCall = $referenceSearchService->get(InterfaceToCallRegistry::REFERENCE_NAME)->getFor($objectToInvokeOn, $this->methodNameOrInterface);

        if (! $interfaceToCall->doesItReturnIterable()) {
            throw InvalidArgumentException::create("Can't create transformer for {$interfaceToCall}, because method has no return value");
        }

        return new Splitter(
            RequestReplyProducer::createRequestAndSplit(
                $this->outputMessageChannelName,
                MethodInvoker::createWith(
                    $interfaceToCall,
                    $objectToInvokeOn,
                    $this->methodParameterConverterBuilders,
                    $referenceSearchService,
                    $channelResolver,
                    $this->orderedAroundInterceptors,
                    $this->getEndpointAnnotations()
                ),
                $channelResolver
            )
        );
    }

    /**
     * @param object $object
     */
    private function setDirectObject($object): void
    {
        $this->directObject = $object;
    }

    public function __toString()
    {
        $reference = $this->referenceName ? $this->referenceName : get_class($this->directObject);

        return sprintf('Splitter - %s:%s with name `%s` for input channel `%s`', $reference, $this->methodNameOrInterface, $this->getEndpointId(), $this->getInputMessageChannelName());
    }
}
