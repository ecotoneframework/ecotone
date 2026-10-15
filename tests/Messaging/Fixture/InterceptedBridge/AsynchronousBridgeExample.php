<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\InterceptedBridge;

use Ecotone\Api\Attribute\Around;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\ServiceContext;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Interceptor\MethodInvocation;

/**
 * licence Apache-2.0
 */
final class AsynchronousBridgeExample
{
    public int $result = 2;

    #[Asynchronous('async')]
    #[InternalHandler('bridgeExample', endpointId: 'async_bridge_result', outputChannelName: 'bridgeSum')]
    public function result(int $result): int
    {
        $this->result += $result;

        return $this->result;
    }

    #[InternalHandler('bridgeSum')]
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
