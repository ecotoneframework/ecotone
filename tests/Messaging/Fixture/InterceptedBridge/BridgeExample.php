<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\InterceptedBridge;

use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Attribute\ServiceActivator;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

final class BridgeExample
{
    #[ServiceActivator("bridgeExample", outputChannelName: "bridgeSum")]
    public function result(int $result): int
    {
        return $result;
    }

    #[ServiceActivator("bridgeSum")]
    public function sum(int $amount): int
    {
        return $amount + 1;
    }

    #[Around(precedence: 0, pointcut: BridgeExample::class . "::result")]
    public function multiply(MethodInvocation $methodInvocation): int
    {
        return $methodInvocation->proceed()->getPayload() * 3;
    }
}