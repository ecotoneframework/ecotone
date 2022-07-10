<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\Calculating;

use Ecotone\Messaging\Attribute\ClassReference;
use Ecotone\Messaging\Attribute\Interceptor\After;
use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Attribute\Interceptor\Before;
use Ecotone\Messaging\Attribute\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Messaging\Attribute\Parameter\Payload;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

class CalculatorInterceptor
{
    #[Before(pointcut: BeforeMultiplyCalculation::class)]
    public function multiplyBefore(int $amount, array $metadata, BeforeMultiplyCalculation $beforeMultiplyCalculation) : int
    {
        return $amount * $beforeMultiplyCalculation->amount;
    }

    #[Around(pointcut: AroundSumCalculation::class)]
    public function sumAround(MethodInvocation $methodInvocation, AroundSumCalculation $aroundResultCalculation) : int
    {
        $proceed = $methodInvocation->proceed();
        return $proceed + $aroundResultCalculation->amount;
    }

    #[After(pointcut: AfterMultiplyCalculation::class)]
    public function multiplyAfter(int $amount, AfterMultiplyCalculation $afterMultiplyCalculation) : int
    {
        return $amount * $afterMultiplyCalculation->amount;
    }
}