<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Behat\Calculating;

use SimplyCodedSoftware\Messaging\Annotation\Interceptor\After;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\Around;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\Before;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\MethodInterceptor;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

/**
 * Class PreCallAdd
 * @package Fixture\Behat\Calculating
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MethodInterceptor()
 */
class CalculatorInterceptor
{
    /**
     * @param int $amount
     * @param BeforeMultiplyCalculation $beforeMultiplyCalculation
     * @return int
     * @Before(
     *     pointcut="@(Test\SimplyCodedSoftware\Messaging\Fixture\Behat\Calculating\BeforeMultiplyCalculation)",
     *     parameterConverters={
     *          @Payload(parameterName="amount")
     *     }
     * )
     */
    public function multiplyBefore(int $amount, BeforeMultiplyCalculation $beforeMultiplyCalculation) : int
    {
        return $amount * $beforeMultiplyCalculation->amount;
    }

    /**
     * @param int $amount
     * @param AfterSumCalculation $afterMultiplyCalculation
     * @return int
     * @After(
     *     pointcut="@(Test\SimplyCodedSoftware\Messaging\Fixture\Behat\Calculating\AfterSumCalculation)",
     *     parameterConverters={
     *          @Payload(parameterName="amount")
     *     }
     * )
     */
    public function sumAfter(int $amount, AfterSumCalculation $afterMultiplyCalculation) : int
    {
        return $amount + $afterMultiplyCalculation->amount;
    }

    /**
     * @return void
     * @Around(
     *     pointcut="@(Test\SimplyCodedSoftware\Messaging\Fixture\Behat\Calculating\AroundResultCalculation)"
     * )
     */
    public function resultAround() : void
    {
        return;
    }
}