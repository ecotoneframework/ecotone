<?php

namespace Messaging\Handler\Transformer;
use Messaging\Handler\InputOutputMessageHandlerBuilder;
use Messaging\Handler\InterfaceToCall;
use Messaging\Handler\MessageHandlerBuilder;
use Messaging\Handler\Processor\MethodInvoker\MessageArgument;
use Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use Messaging\Handler\Processor\MethodInvoker\PayloadArgument;
use Messaging\MessageChannel;
use Messaging\MessageHandler;
use Messaging\Support\InvalidArgumentException;
use Messaging\Support\MessageBuilder;

/**
 * Class TransformerBuilder
 * @package Messaging\Handler\Transformer
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TransformerBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilder
{
    /**
     * @var MessageChannel
     */
    private $inputChannel;
    /**
     * @var MessageChannel
     */
    private $outputChannel;
    /**
     * @var object
     */
    private $objectToInvoke;
    /**
     * @var string
     */
    private $methodName;

    /**
     * TransformerBuilder constructor.
     * @param MessageChannel $inputChannel
     * @param MessageChannel $outputChannel
     * @param $objectToInvoke
     * @param string $methodName
     * @param string $handlerName
     */
    public function __construct(MessageChannel $inputChannel, MessageChannel $outputChannel, $objectToInvoke, string $methodName, string $handlerName)
    {
        $this->inputChannel = $inputChannel;
        $this->outputChannel = $outputChannel;
        $this->objectToInvoke = $objectToInvoke;
        $this->methodName = $methodName;

        $this->withName($handlerName);
    }

    /**
     * @param MessageChannel $inputChannel
     * @param MessageChannel $outputChannel
     * @param $objectToInvoke
     * @param string $methodName
     * @param string $handlerName
     * @return TransformerBuilder
     */
    public static function create(MessageChannel $inputChannel, MessageChannel $outputChannel, $objectToInvoke, string $methodName, string $handlerName): self
    {
        return new self($inputChannel, $outputChannel, $objectToInvoke, $methodName, $handlerName);
    }

    /**
     * @inheritDoc
     */
    public function build(): MessageHandler
    {
        $interfaceToCall = InterfaceToCall::createFromObject($this->objectToInvoke, $this->methodName);
        $firstParameterName = $interfaceToCall->getFirstParameterName();
        $messageArguments = [];

        if ($interfaceToCall->isVoid()) {
            throw InvalidArgumentException::create("Can't create transformer for {$interfaceToCall}, because method has no return value");
        }

        if ($interfaceToCall->hasFirstParameterMessageTypeHint()) {
            $messageArguments[] = MessageArgument::create($firstParameterName);
        }else {
            $messageArguments[] = PayloadArgument::create($firstParameterName);
        }

        return new Transformer(
            $this->outputChannel,
            MethodInvoker::createWith($this->objectToInvoke, $this->methodName, $messageArguments)
        );
    }
}