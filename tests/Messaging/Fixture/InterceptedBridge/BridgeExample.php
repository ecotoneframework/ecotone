<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\InterceptedBridge;

use Ecotone\Api\Attribute\Around;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Interceptor\MethodInvocation;

/**
 * licence Apache-2.0
 */
final class BridgeExample
{
    #[InternalHandler('bridgeExample', outputChannelName: 'bridgeSum')]
    public function result(int $result): int
    {
        return $result;
    }

    #[InternalHandler('bridgeSum')]
    public function sum(int $amount): int
    {
        return $amount + 1;
    }

    #[Around(precedence: 0, pointcut: BridgeExample::class . '::result')]
    public function multiply(MethodInvocation $methodInvocation): int
    {
        return $methodInvocation->proceed() * 3;
    }
}
