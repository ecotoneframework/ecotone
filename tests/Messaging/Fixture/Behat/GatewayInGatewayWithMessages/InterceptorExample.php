<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Behat\GatewayInGatewayWithMessages;

use Ecotone\Messaging\Annotation\Interceptor\After;
use Ecotone\Messaging\Annotation\Interceptor\Around;
use Ecotone\Messaging\Annotation\Interceptor\Before;
use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;

class InterceptorExample
{
    /**
     * @Before(pointcut="Test\Ecotone\Messaging\Fixture\Behat\GatewayInGatewayWithMessages\SomeQueryHandler::sum")
     */
    public function multiplyBefore(Message $message): Message
    {
        return MessageBuilder::fromMessage($message)
                ->setPayload($message->getPayload() * 2)
                ->build();
    }

    /**
     * @Around(pointcut="Test\Ecotone\Messaging\Fixture\Behat\GatewayInGatewayWithMessages\SomeQueryHandler::multiply")
     */
    public function sum(MethodInvocation $methodInvocation): Message
    {
        $message = $methodInvocation->proceed();

        return MessageBuilder::fromMessage($message)
            ->setPayload($message->getPayload() + 1)
            ->build();
    }

    /**
     * @After(pointcut="Test\Ecotone\Messaging\Fixture\Behat\GatewayInGatewayWithMessages\SomeQueryHandler::sum")
     */
    public function multiplyAfter(Message $message): Message
    {
        return MessageBuilder::fromMessage($message)
            ->setPayload($message->getPayload() * 2)
            ->build();
    }
}