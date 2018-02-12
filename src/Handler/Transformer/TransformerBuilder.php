<?php

namespace SimplyCodedSoftware\Messaging\Handler\Transformer;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\Messaging\Handler\MethodParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\MethodParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MessageParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\PayloadParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\RequestReplyProducer;
use SimplyCodedSoftware\Messaging\MessageHandler;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

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
     * @var MethodParameterConverterBuilder[]|array
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
     * @param array|MethodParameterConverter[] $methodParameterConverterBuilders
     * @return void
     */
    public function withMethodParameterConverters(array $methodParameterConverterBuilders) : void
    {
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
            RequestReplyProducer::createFrom(
                $channelResolver->resolve($this->outputMessageChannelName),
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