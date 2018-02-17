<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToPayloadParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\RequestReplyProducer;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Class TransformerBuilder
 * @package Messaging\Handler\Transformer
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TransformerBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithParameterConverters
{
    /**
     * @var string
     */
    private $objectToInvokeReferenceName;
    /**
     * @var object
     */
    private $object;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var MessageToParameterConverterBuilder[]|array
     */
    private $methodParameterConverterBuilders = [];
    /**
     * @var string[]
     */
    private $requiredReferenceNames = [];

    /**
     * TransformerBuilder constructor.
     * @param string $inputChannelName
     * @param string $outputChannelName
     * @param string $objectToInvokeReference
     * @param string $methodName
     * @param string $consumerName
     */
    private function __construct(string $inputChannelName, string $outputChannelName, string $objectToInvokeReference, string $methodName, string $consumerName)
    {
        $this->objectToInvokeReferenceName = $objectToInvokeReference;
        $this->methodName = $methodName;

        $this->withInputMessageChannel($inputChannelName);
        $this->withOutputMessageChannel($outputChannelName);
        $this->withName($consumerName);
    }

    /**
     * @param string $inputChannelName
     * @param string $outputChannelName
     * @param string $objectToInvokeReference
     * @param string $methodName
     * @param string $consumerName
     * @return TransformerBuilder
     */
    public static function create(string $inputChannelName, string $outputChannelName, string $objectToInvokeReference, string $methodName, string $consumerName): self
    {
        return new self($inputChannelName, $outputChannelName, $objectToInvokeReference, $methodName, $consumerName);
    }

    /**
     * @param string $consumerName
     * @param string $inputChannelName
     * @param string $outputChannelName
     * @param array|string[] $messageHeaders
     * @return TransformerBuilder
     */
    public static function createHeaderEnricher(string $consumerName, string $inputChannelName, string $outputChannelName, array $messageHeaders) : self
    {
        $transformerBuilder = new self($inputChannelName, $outputChannelName, "", "transform", $consumerName);
        $transformerBuilder->setDirectObjectToInvoke(HeaderEnricher::create($messageHeaders));

        return $transformerBuilder;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        $requiredReferenceNames = $this->requiredReferenceNames;
        $requiredReferenceNames[] = $this->objectToInvokeReferenceName;

        return $requiredReferenceNames;
    }

    /**
     * @inheritDoc
     */
    public function registerRequiredReference(string $referenceName): void
    {
        $this->requiredReferenceNames[] = $referenceName;
    }

    /**
     * @param array|MessageToParameterConverter[] $methodParameterConverterBuilders
     * @return void
     */
    public function withMethodParameterConverters(array $methodParameterConverterBuilders) : void
    {
        Assert::allInstanceOfType($methodParameterConverterBuilders, MessageToParameterConverterBuilder::class);

       $this->methodParameterConverterBuilders = $methodParameterConverterBuilders;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService) : MessageHandler
    {
        $objectToInvokeOn = $this->object ? $this->object : $referenceSearchService->findByReference($this->objectToInvokeReferenceName);
        $interfaceToCall = InterfaceToCall::createFromObject($objectToInvokeOn, $this->methodName);

        if ($interfaceToCall->doesItNotReturnValue()) {
            throw InvalidArgumentException::create("Can't create transformer for {$interfaceToCall}, because method has no return value");
        }

        $methodParameterConverters = [];
        foreach ($this->methodParameterConverterBuilders as $methodParameterConverterBuilder) {
            $methodParameterConverters[] = $methodParameterConverterBuilder->build($referenceSearchService);
        }

        return new Transformer(
            RequestReplyProducer::createRequestAndReply(
                $this->outputMessageChannelName,
                TransformerMessageProcessor::createFrom(
                    MethodInvoker::createWith(
                        $objectToInvokeOn,
                        $this->methodName,
                        $methodParameterConverters
                    )
                ),
                $channelResolver,
                false
            )
        );
    }



    public function __toString()
    {
        return "transformer";
    }

    /**
     * @param object $objectToInvoke
     */
    private function setDirectObjectToInvoke($objectToInvoke) : void
    {
        $this->object = $objectToInvoke;
    }
}