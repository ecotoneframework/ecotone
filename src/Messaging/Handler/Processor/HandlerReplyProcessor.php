<?php

namespace Ecotone\Messaging\Handler\Processor;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodCall;
use Ecotone\Messaging\Handler\RequestReplyProducer;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;
use Ramsey\Uuid\Uuid;

class HandlerReplyProcessor implements MessageProcessor
{
    public function __construct(private RequestReplyProducer $requestReplyProducer)
    {
    }

    public function executeEndpoint(Message $message): ?Message
    {
        $bridge = QueueChannel::create('request-reply-' . Uuid::uuid4());
        $message = MessageBuilder::fromMessage($message)
            ->setReplyChannel($bridge)
            ->build();

        $this->requestReplyProducer->handle($message);

        return $bridge->receive();
    }

    public function getMethodCall(Message $message): MethodCall
    {
        return $this->requestReplyProducer->getMessageProcessor()->getMethodCall($message);
    }

    public function getObjectToInvokeOn(): string|object
    {
        return $this->requestReplyProducer->getMessageProcessor()->getObjectToInvokeOn();
    }
}
