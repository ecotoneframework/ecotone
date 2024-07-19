<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\Calculating;

use Ecotone\Messaging\Attribute\Interceptor\After;
use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Attribute\Interceptor\Before;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

/**
 * licence Apache-2.0
 */
class CalculatorInterceptor
{
    #[Before(pointcut: BeforeMultiplyCalculation::class)]
    public function multiplyBefore(int $amount, array $metadata, BeforeMultiplyCalculation $beforeMultiplyCalculation): int
    {
        return $amount * $beforeMultiplyCalculation->amount;
    }

    #[Around(pointcut: AroundSumCalculation::class)]
    public function sumAround(MethodInvocation $methodInvocation, AroundSumCalculation $aroundResultCalculation): int
    {
        $proceed = $methodInvocation->proceed();
        if (! $proceed) {
            return $aroundResultCalculation->amount;
        }

        return $proceed->getPayload() + $aroundResultCalculation->amount;
    }

    #[After(pointcut: AfterMultiplyCalculation::class)]
    public function multiplyAfter(int $amount, AfterMultiplyCalculation $afterMultiplyCalculation): int
    {
        return $amount * $afterMultiplyCalculation->amount;
    }
}
