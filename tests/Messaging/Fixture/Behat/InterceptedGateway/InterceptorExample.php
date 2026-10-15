<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Behat\InterceptedGateway;

use Ecotone\Api\Attribute\After;
use Ecotone\Api\Attribute\Around;
use Ecotone\Api\Attribute\Before;
use Ecotone\Api\Interceptor\MethodInvocation;

/**
 * licence Apache-2.0
 */
class InterceptorExample
{
    #[Before(pointcut: CalculateGatewayExample::class)]
    public function multiplyBefore(int $amount): int
    {
        return $amount * 2;
    }

    #[Around(pointcut: CalculateGatewayExample::class)]
    public function sum(MethodInvocation $methodInvocation): int
    {
        $proceed = $methodInvocation->proceed();
        return $proceed->getPayload() + 1;
    }

    #[After(pointcut: CalculateGatewayExample::class)]
    public function multiplyAfter(int $amount): int
    {
        return $amount * 2;
    }
}
