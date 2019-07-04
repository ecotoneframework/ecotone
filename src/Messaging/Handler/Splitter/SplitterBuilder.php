<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Splitter;

use SimplyCodedSoftware\Messaging\Config\ReferenceTypeFromNameResolver;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCallRegistry;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\Messaging\Handler\ParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\RequestReplyProducer;
use SimplyCodedSoftware\Messaging\MessageHandler;
use SimplyCodedSoftware\Messaging\Support\Assert;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class SplitterBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Splitter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SplitterBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithParameterConverters, MessageHandlerBuilderWithOutputChannel
{
    /**
     * @var string
     */
    private $referenceName;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var array|\SimplyCodedSoftware\Messaging\Handler\ParameterConverterBuilder[]
     */
    private $methodParameterConverterBuilders = [];
    /**
     * @var string[]
     */
    private $requiredReferenceNames = [];
    /**
     * @var object
     */
    private $directObject;

    /**
     * ServiceActivatorBuilder constructor.
     * @param string $referenceName
     * @param string $methodName
     */
    private function __construct(string $referenceName, string $methodName)
    {
        $this->referenceName = $referenceName;
        $this->methodName = $methodName;

        if ($referenceName) {
            $this->requiredReferenceNames[] = $referenceName;
        }
    }

    /**
     * @param string $referenceName
     * @param string $methodName
     * @return SplitterBuilder
     */
    public static function create(string $referenceName, string $methodName): self
    {
        return new self($referenceName, $methodName);
    }

    /**
     * @inheritDoc
     */
    public function resolveRelatedReferences(InterfaceToCallRegistry $interfaceToCallRegistry) : iterable
    {
        return [
            $this->directObject
                ? $interfaceToCallRegistry->getFor($this->directObject, $this->methodName)
                : $interfaceToCallRegistry->getForReferenceName($this->referenceName, $this->methodName)
        ];
    }

    /**
     * Splits directly from message payload, without using any service
     *
     * @return SplitterBuilder
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createMessagePayloadSplitter() : self
    {
        return self::createWithDirectObject(new DirectMessageSplitter(), "split");
    }

    /**
     * @param object $directReferenceObject
     * @param string $methodName
     * @return SplitterBuilder
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createWithDirectObject($directReferenceObject, string $methodName): self
    {
        Assert::isObject($directReferenceObject, "Direct reference must be object");

        $splitterBuilder = new self("", $methodName);
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
    public function withMethodParameterConverters(array $methodParameterConverterBuilders)
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
        return $this->referenceName ? $interfaceToCallRegistry->getForReferenceName($this->referenceName, $this->methodName) : $interfaceToCallRegistry->getFor($this->directObject, $this->methodName);
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        $objectToInvokeOn = $this->directObject ? $this->directObject : $referenceSearchService->get($this->referenceName);
        $interfaceToCall = $referenceSearchService->get(InterfaceToCallRegistry::REFERENCE_NAME)->getFor($objectToInvokeOn, $this->methodName);

        if (!$interfaceToCall->doesItReturnIterable()) {
            throw InvalidArgumentException::create("Can't create transformer for {$interfaceToCall}, because method has no return value");
        }

        return new Splitter(
            RequestReplyProducer::createRequestAndSplit(
                $this->outputMessageChannelName,
                MethodInvoker::createWithInterceptors(
                    $objectToInvokeOn,
                    $this->methodName,
                    $this->methodParameterConverterBuilders,
                    $referenceSearchService,
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
    private function setDirectObject($object) : void
    {
        $this->directObject = $object;
    }

    public function __toString()
    {
        $reference = $this->referenceName ? $this->referenceName : get_class($this->directObject);

        return sprintf("Splitter - %s:%s with name `%s` for input channel `%s`", $reference, $this->methodName, $this->getEndpointId(), $this->getInputMessageChannelName());
    }
}