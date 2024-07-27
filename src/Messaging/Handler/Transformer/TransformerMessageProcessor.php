<?php

namespace Ecotone\Messaging\Handler\Transformer;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class TransformerMessageProcessor
 * @package Messaging\Handler\Transformer
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 * @internal
 */
/**
 * licence Apache-2.0
 */
class TransformerMessageProcessor implements MessageProcessor
{
    public function __construct(private MethodInvoker $methodInvoker, private Type $returnType)
    {
    }

    /**
     * @inheritDoc
     */
    public function executeEndpoint(Message $message): ?Message
    {
        $reply = $this->methodInvoker->executeEndpoint($message);
        $replyBuilder = MessageBuilder::fromMessage($message);

        if (is_null($reply)) {
            return null;
        }

        if (is_array($reply)) {
            $reply = $replyBuilder
                ->setMultipleHeaders($reply)
                ->build();
        } elseif (! ($reply instanceof Message)) {
            $reply = $replyBuilder
                ->setPayload($reply)
                ->setContentType(MediaType::createApplicationXPHPWithTypeParameter($this->returnType->toString()))
                ->build();
        }

        return $reply;
    }

    public function getMethodCall(Message $message): MethodCall
    {
        return $this->methodInvoker->getMethodCall($message);
    }

    public function getObjectToInvokeOn(): string|object
    {
        return $this->methodInvoker->getObjectToInvokeOn();
    }

    public function getMethodName(): string
    {
        return $this->methodInvoker->getMethodName();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->methodInvoker;
    }
}
