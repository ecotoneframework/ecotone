<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\Calculating;

use Ecotone\Messaging\Annotation\ClassReference;
use Ecotone\Messaging\Annotation\Interceptor\After;
use Ecotone\Messaging\Annotation\Interceptor\Around;
use Ecotone\Messaging\Annotation\Interceptor\Before;
use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

class CalculatorInterceptor
{
    #[Before(pointcut: "@(" . BeforeMultiplyCalculation::class . ")")]
    public function multiplyBefore(int $amount, array $metadata, BeforeMultiplyCalculation $beforeMultiplyCalculation) : int
    {
        return $amount * $beforeMultiplyCalculation->amount;
    }

    #[Around(pointcut: "@(" . AroundSumCalculation::class . ")")]
    public function sumAround(MethodInvocation $methodInvocation, AroundSumCalculation $aroundResultCalculation) : int
    {
        $proceed = $methodInvocation->proceed();
        return $proceed + $aroundResultCalculation->amount;
    }

    #[After(pointcut: "@(" . AfterMultiplyCalculation::class . "")]
    public function multiplyAfter(int $amount, AfterMultiplyCalculation $afterMultiplyCalculation) : int
    {
        return $amount * $afterMultiplyCalculation->amount;
    }
}