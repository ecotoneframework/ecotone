<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\InterceptedBridge;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Attribute\ServiceActivator;
use Ecotone\Messaging\Attribute\ServiceContext;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

final class AsynchronousBridgeExample
{
    public int $result = 2;

    #[Asynchronous('async')]
    #[ServiceActivator('bridgeExample', outputChannelName: 'bridgeSum')]
    public function result(int $result): int
    {
        $this->result += $result;

        return $this->result;
    }

    #[ServiceActivator('bridgeSum')]
    public function sum(int $amount): int
    {
        $this->result += $amount;

        return $this->result;
    }

    #[Around(precedence: 0, pointcut: AsynchronousRunningEndpoint::class)]
    public function multiply(MethodInvocation $methodInvocation)
    {
        $this->result *= 2;
        $result = $methodInvocation->proceed();
        $this->result *= 3;

        return $result;
    }

    #[ServiceContext]
    public function config()
    {
        return SimpleMessageChannelBuilder::createQueueChannel('async');
    }
}
