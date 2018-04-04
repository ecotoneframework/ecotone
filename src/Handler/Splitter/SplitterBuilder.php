<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Splitter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithOutputChannel;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\RequestReplyProducer;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Class SplitterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Splitter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SplitterBuilder implements MessageHandlerBuilderWithParameterConverters, MessageHandlerBuilderWithOutputChannel
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
     * @var string
     */
    private $inputMessageChannelName;
    /**
     * @var string
     */
    private $outputChannelName = "";
    /**
     * @var array|\SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverterBuilder[]
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
     * @param string $inputChannelName
     * @param string $referenceName
     * @param string $methodName
     */
    private function __construct(string $inputChannelName, string $referenceName, string $methodName)
    {
        $this->inputMessageChannelName = $inputChannelName;
        $this->referenceName = $referenceName;
        $this->methodName = $methodName;

        if ($referenceName) {
            $this->requiredReferenceNames[] = $referenceName;
        }
    }

    /**
     * @param string $inputChannelName
     * @param string $referenceName
     * @param string $methodName
     * @return SplitterBuilder
     */
    public static function create(string $inputChannelName, string $referenceName, string $methodName): self
    {
        return new self($inputChannelName, $referenceName, $methodName);
    }

    /**
     * @param string $inputChannelName
     * @param object $directReferenceObject
     * @param string $methodName
     * @return SplitterBuilder
     */
    public static function createWithDirectObject(string $inputChannelName, $directReferenceObject, string $methodName): self
    {
        Assert::isObject($directReferenceObject, "Direct reference must be object");

        $splitterBuilder = new self($inputChannelName, "", $methodName);
        $splitterBuilder->setDirectObject($directReferenceObject);

        return $splitterBuilder;
    }

    /**
     * @inheritDoc
     */
    public function getInputMessageChannelName(): string
    {
        return $this->inputMessageChannelName;
    }

    /**
     * @param string $messageChannelName
     * @return self
     */
    public function withOutputMessageChannel(string $messageChannelName): self
    {
        $this->outputChannelName = $messageChannelName;

        return $this;
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
    public function withMethodParameterConverters(array $methodParameterConverterBuilders): void
    {
        Assert::allInstanceOfType($methodParameterConverterBuilders, MessageToParameterConverterBuilder::class);

        $this->methodParameterConverterBuilders = $methodParameterConverterBuilders;
    }

    /**
     * @inheritDoc
     */
    public function registerRequiredReference(string $referenceName): void
    {
        $this->requiredReferenceNames[] = $referenceName;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        $objectToInvokeOn = $this->directObject ? $this->directObject : $referenceSearchService->findByReference($this->referenceName);
        $interfaceToCall = InterfaceToCall::createFromObject($objectToInvokeOn, $this->methodName);

        if (!$interfaceToCall->doesItReturnArray()) {
            throw InvalidArgumentException::create("Can't create transformer for {$interfaceToCall}, because method has no return value");
        }

        $methodParameterConverters = [];
        foreach ($this->methodParameterConverterBuilders as $methodParameterConverterBuilder) {
            $methodParameterConverters[] = $methodParameterConverterBuilder->build($referenceSearchService);
        }

        return new Splitter(
            RequestReplyProducer::createRequestAndSplit(
                $this->outputChannelName,
                MethodInvoker::createWith(
                    $objectToInvokeOn,
                    $this->methodName,
                    $methodParameterConverters
                )
                ,
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
}