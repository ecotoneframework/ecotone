<?php

namespace Ecotone\Messaging\Handler\Transformer;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class TransformerMessageProcessor
 * @package Messaging\Handler\Transformer
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class TransformerMessageProcessor implements MessageProcessor
{
    private \Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvoker $methodInvoker;

    /**
     * TransformerMessageProcessor constructor.
     * @param MethodInvoker $methodInvoker
     */
    private function __construct(MethodInvoker $methodInvoker)
    {
        $this->methodInvoker = $methodInvoker;
    }

    /**
     * @param MethodInvoker $methodInvoker
     * @return TransformerMessageProcessor
     */
    public static function createFrom(MethodInvoker $methodInvoker) : self
    {
        return new self($methodInvoker);
    }

    /**
     * @inheritDoc
     */
    public function processMessage(Message $message): ?\Ecotone\Messaging\Message
    {
        $reply = $this->methodInvoker->processMessage($message);
        $replyBuilder = MessageBuilder::fromMessage($message);

        if (is_null($reply)) {
            return null;
        }

        if (is_array($reply)) {
            $reply = $replyBuilder
                ->setMultipleHeaders($reply)
                ->build();
        }else if (!($reply instanceof Message)) {
            $reply = $replyBuilder
                ->setPayload($reply)
                ->setContentType(MediaType::createApplicationXPHPWithTypeParameter($this->methodInvoker->getInterfaceToCall()->getReturnType()->toString()))
                ->build();
        }

        return $reply;
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        return (string)$this->methodInvoker;
    }
}