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
    /**
     * @param int $amount
     * @param array $metadata
     * @param BeforeMultiplyCalculation $beforeMultiplyCalculation
     * @return int
     * @Before(
     *     pointcut="@(Test\Ecotone\Messaging\Fixture\Behat\Calculating\BeforeMultiplyCalculation)"
     * )
     */
    public function multiplyBefore(int $amount, array $metadata, BeforeMultiplyCalculation $beforeMultiplyCalculation) : int
    {
        return $amount * $beforeMultiplyCalculation->amount;
    }

    /**
     * @param MethodInvocation $methodInvocation
     * @param AroundSumCalculation $aroundResultCalculation
     * @return int
     * @Around(
     *     pointcut="@(Test\Ecotone\Messaging\Fixture\Behat\Calculating\AroundSumCalculation)"
     * )
     */
    public function sumAround(MethodInvocation $methodInvocation, AroundSumCalculation $aroundResultCalculation) : int
    {
        $proceed = $methodInvocation->proceed();
        return $proceed + $aroundResultCalculation->amount;
    }

    /**
     * @param int $amount
     * @param AfterMultiplyCalculation $afterMultiplyCalculation
     * @return int
     * @After(
     *     pointcut="@(Test\Ecotone\Messaging\Fixture\Behat\Calculating\AfterMultiplyCalculation)"
     * )
     */
    public function multiplyAfter(int $amount, AfterMultiplyCalculation $afterMultiplyCalculation) : int
    {
        return $amount * $afterMultiplyCalculation->amount;
    }
}