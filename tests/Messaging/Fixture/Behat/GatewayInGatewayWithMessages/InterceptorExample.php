<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Behat\GatewayInGatewayWithMessages;

use Ecotone\Messaging\Attribute\Interceptor\After;
use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Attribute\Interceptor\Before;
use Ecotone\Messaging\Attribute\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;

class InterceptorExample
{
    #[Before(pointcut: SomeQueryHandler::class . "::sum")]
    public function multiplyBefore(Message $message): Message
    {
        return MessageBuilder::fromMessage($message)
                ->setPayload($message->getPayload() * 2)
                ->build();
    }

    #[Around(pointcut: SomeQueryHandler::class . "::multiply")]
    public function sum(MethodInvocation $methodInvocation): Message
    {
        $message = $methodInvocation->proceed();

        return MessageBuilder::fromMessage($message)
            ->setPayload($message->getPayload() + 1)
            ->build();
    }

    #[After(pointcut: SomeQueryHandler::class . "::sum")]
    public function multiplyAfter(Message $message): Message
    {
        return MessageBuilder::fromMessage($message)
            ->setPayload($message->getPayload() * 2)
            ->build();
    }
}