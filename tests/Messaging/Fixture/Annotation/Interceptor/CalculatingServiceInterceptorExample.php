<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Interceptor;

use SimplyCodedSoftware\Messaging\Annotation\Interceptor\Around;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\MethodInterceptor;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

/**
 * Class CalculatingService
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MethodInterceptor(referenceName="calculatingService")
 */
class CalculatingServiceInterceptorExample
{
    /**
     * @var int
     */
    private $secondValueForMathOperations;

    /**
     * @param int $secondValueForMathOperations
     * @return CalculatingServiceInterceptorExample
     */
    public static function create(int $secondValueForMathOperations) : self
    {
        $calculatingService = new self();
        $calculatingService->secondValueForMathOperations = $secondValueForMathOperations;

        return $calculatingService;
    }

    /**
     * @param int $amount
     * @return int
     */
    public function result(int $amount) : int
    {
        return $amount;
    }

    /**
     * @param MethodInvocation $methodInvocation
     * @param int $amount
     * @return int
     * @Around(precedence=2, pointcut=CalculatingServiceInterceptorExample::class)
     */
    public function sum(MethodInvocation $methodInvocation, int $amount) : int
    {
        $result = $amount + $this->secondValueForMathOperations;

        $methodInvocation->replaceArgument("amount", $result);
        return $methodInvocation->proceed();
    }

    /**
     * @param MethodInvocation $methodInvocation
     * @param int $amount
     * @return int
     * @Around()
     */
    public function subtract(MethodInvocation $methodInvocation, int $amount) : int
    {
        $result = $amount - $this->secondValueForMathOperations;

        $methodInvocation->replaceArgument("amount", $result);
        return $methodInvocation->proceed();
    }

    /**
     * @param MethodInvocation $methodInvocation
     * @param int $amount
     * @return int
     * @Around(precedence=2, pointcut=CalculatingServiceInterceptorExample::class)
     */
    public function multiply(MethodInvocation $methodInvocation, int $amount) : int
    {
        $result = $amount * $this->secondValueForMathOperations;

        $methodInvocation->replaceArgument("amount", $result);
        return $methodInvocation->proceed();
    }
}