<?php

namespace SimplyCodedSoftware\Messaging\Handler\Transformer;
use SimplyCodedSoftware\Messaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\MethodArgument;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MessageArgument;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\PayloadArgument;
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
     * @var object
     */
    private $objectToInvoke;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var MethodArgument[]|array
     */
    private $methodArguments;

    /**
     * TransformerBuilder constructor.
     * @param string $inputChannelName
     * @param string $outputChannelName
     * @param $objectToInvoke
     * @param string $methodName
     * @param string $handlerName
     */
    private function __construct(string $inputChannelName, string $outputChannelName, $objectToInvoke, string $methodName, string $handlerName)
    {
        $this->objectToInvoke = $objectToInvoke;
        $this->methodName = $methodName;

        $this->withInputMessageChannel($inputChannelName);
        $this->withOutputMessageChannel($outputChannelName);
        $this->withName($handlerName);
    }

    /**
     * @param string $inputChannelName
     * @param string $outputChannelName
     * @param $objectToInvoke
     * @param string $methodName
     * @param string $handlerName
     * @return TransformerBuilder
     */
    public static function create(string $inputChannelName, string $outputChannelName, $objectToInvoke, string $methodName, string $handlerName): self
    {
        return new self($inputChannelName, $outputChannelName, $objectToInvoke, $methodName, $handlerName);
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
        return new self($inputChannelName, $outputChannelName, HeaderEnricher::create($messageHeaders), "transform", $handlerName);
    }

    /**
     * @param array|MethodArgument[] $methodArguments
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
        $interfaceToCall = InterfaceToCall::createFromObject($this->objectToInvoke, $this->methodName);
        $firstParameterName = $interfaceToCall->getFirstParameterName();
        $methodArguments = $this->methodArguments;

        if ($interfaceToCall->doesItReturnValue()) {
            throw InvalidArgumentException::create("Can't create transformer for {$interfaceToCall}, because method has no return value");
        }

        if (empty($methodArguments)) {
            if ($interfaceToCall->hasFirstParameterMessageTypeHint()) {
                $methodArguments[] = MessageArgument::create($firstParameterName);
            }else {
                $methodArguments[] = PayloadArgument::create($firstParameterName);
            }
        }

        return new Transformer(
            RequestReplyProducer::createFrom(
                $this->channelResolver->resolve($this->outputMessageChannelName),
                TransformerMessageProcessor::createFrom(
                    MethodInvoker::createWith($this->objectToInvoke, $this->methodName, $methodArguments)
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
}