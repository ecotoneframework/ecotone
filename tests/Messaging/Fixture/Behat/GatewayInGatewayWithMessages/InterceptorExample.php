<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Behat\GatewayInGatewayWithMessages;

use Ecotone\Api\Attribute\After;
use Ecotone\Api\Attribute\Around;
use Ecotone\Api\Attribute\Before;
use Ecotone\Api\Interceptor\MethodInvocation;
use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * licence Apache-2.0
 */
class InterceptorExample
{
    #[Before(pointcut: SomeQueryHandler::class . '::sum')]
    public function multiplyBefore(Message $message): Message
    {
        return MessageBuilder::fromMessage($message)
                ->setPayload($message->getPayload() * 2)
                ->build();
    }

    #[Around(pointcut: SomeQueryHandler::class . '::multiply')]
    public function sum(MethodInvocation $methodInvocation): Message
    {
        $message = $methodInvocation->proceed();

        return MessageBuilder::fromMessage($message)
            ->setPayload($message->getPayload() + 1)
            ->build();
    }

    #[After(pointcut: SomeQueryHandler::class . '::sum')]
    public function multiplyAfter(Message $message): Message
    {
        return MessageBuilder::fromMessage($message)
            ->setPayload($message->getPayload() * 2)
            ->build();
    }
}
