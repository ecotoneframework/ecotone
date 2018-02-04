<?php

namespace SimplyCodedSoftware\Messaging\Handler\Transformer;
use SimplyCodedSoftware\Messaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\MethodParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MessageParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\PayloadParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\RequestReplyProducer;
use SimplyCodedSoftware\Messaging\MessageHandler;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class TransformerBuilder
 * @package Messaging\Handler\Transformer
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TransformerBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilder
{
    /**
     * @var string
     */
    private $objectToInvokeReference;
    /**
     * @var object
     */
    private $object;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var MethodParameterConverter[]|array
     */
    private $methodArguments;

    /**
     * TransformerBuilder constructor.
     * @param string $inputChannelName
     * @param string $outputChannelName
     * @param string $objectToInvokeReference
     * @param string $methodName
     * @param string $handlerName
     */
    private function __construct(string $inputChannelName, string $outputChannelName, string $objectToInvokeReference, string $methodName, string $handlerName)
    {
        $this->objectToInvokeReference = $objectToInvokeReference;
        $this->methodName = $methodName;

        $this->withInputMessageChannel($inputChannelName);
        $this->withOutputMessageChannel($outputChannelName);
        $this->withName($handlerName);
    }

    /**
     * @param string $inputChannelName
     * @param string $outputChannelName
     * @param string $objectToInvokeReference
     * @param string $methodName
     * @param string $handlerName
     * @return TransformerBuilder
     */
    public static function create(string $inputChannelName, string $outputChannelName, string $objectToInvokeReference, string $methodName, string $handlerName): self
    {
        return new self($inputChannelName, $outputChannelName, $objectToInvokeReference, $methodName, $handlerName);
    }

    /**
     * @param string $handlerName
     * @param string $inputChannelName
     * @param string $outputChannelName
     * @param array $messageHeaders
     * @return TransformerBuilder
     */
    public static function createHeaderEnricher(string $handlerName, string $inputChannelName, string $outputChannelName, array $messageHeaders) : self
    {
        $transformerBuilder = new self($inputChannelName, $outputChannelName, "", "transform", $handlerName);
        $transformerBuilder->setDirectObjectToInvoke(HeaderEnricher::create($messageHeaders));

        return $transformerBuilder;
    }

    /**
     * @param array|MethodParameterConverter[] $methodArguments
     * @return TransformerBuilder
     */
    public function withMethodArguments(array $methodArguments) : self
    {
       $this->methodArguments = $methodArguments;

       return $this;
    }

    /**
     * @inheritDoc
     */
    public function build(): MessageHandler
    {
        $objectToInvokeOn = $this->object ? $this->object : $this->referenceSearchService->findByReference($this->objectToInvokeReference);
        $interfaceToCall = InterfaceToCall::createFromObject($objectToInvokeOn, $this->methodName);
        $firstParameterName = $interfaceToCall->getFirstParameterName();
        $methodArguments = $this->methodArguments;

        if ($interfaceToCall->doesItReturnValue()) {
            throw InvalidArgumentException::create("Can't create transformer for {$interfaceToCall}, because method has no return value");
        }

        if (empty($methodArguments)) {
            if ($interfaceToCall->hasFirstParameterMessageTypeHint()) {
                $methodArguments[] = MessageParameterConverter::create($firstParameterName);
            }else {
                $methodArguments[] = PayloadParameterConverter::create($firstParameterName);
            }
        }

        return new Transformer(
            RequestReplyProducer::createFrom(
                $this->channelResolver->resolve($this->outputMessageChannelName),
                TransformerMessageProcessor::createFrom(
                    MethodInvoker::createWith(
                        $objectToInvokeOn,
                        $this->methodName,
                        $methodArguments
                    )
                ),
                $this->channelResolver,
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