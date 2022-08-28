<?php

namespace Ecotone\Messaging\Handler\Interceptor;

use Ecotone\Messaging\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;

final class ConsumerNameInterceptor
{
    public function intercept(Message $message, AsynchronousRunningEndpoint $attribute): Message
    {
        return MessageBuilder::fromMessage($message)
            ->setHeader(MessageHeaders::CONSUMER_ENDPOINT_ID, $attribute->getEndpointId())
            ->build();
    }
}