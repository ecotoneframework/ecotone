<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\Interceptor;

use Ecotone\Messaging\Attribute\ClassReference;
use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Attribute\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

#[ClassReference("calculatingService")]
class CalculatingServiceInterceptorExample
{
    /**
     * @var int
     */
    private $secondValueForMathOperations;
    /**
     * @var bool
     */
    private $wasCalled = false;

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
     * @param MethodInvocation $methodInvocation
     * @param int $amount
     * @return int
     */
    public function result(MethodInvocation $methodInvocation, int $amount) : int
    {
        return $amount;
    }

    #[Around(2, CalculatingServiceInterceptorExample::class)]
    public function sum(MethodInvocation $methodInvocation, int $amount)
    {
        $result = $amount + $this->secondValueForMathOperations;

        $methodInvocation->replaceArgument("amount", $result);
        return $methodInvocation->proceed();
    }

    /**
     * @param MethodInvocation $methodInvocation
     * @return integer
     */
    public function sumAfterCalling(MethodInvocation $methodInvocation) : int
    {
        $result = $methodInvocation->proceed();

        return $this->secondValueForMathOperations + $result;
    }

    public function resultAfterCalling(MethodInvocation $methodInvocation)
    {
        return $methodInvocation->proceed();
    }

    #[Around]
    public function subtract(MethodInvocation $methodInvocation, int $amount) : int
    {
        $result = $amount - $this->secondValueForMathOperations;

        $methodInvocation->replaceArgument("amount", $result);
        return $methodInvocation->proceed();
    }

    #[Around(2, CalculatingServiceInterceptorExample::class)]
    public function multiply(MethodInvocation $methodInvocation, int $amount) : int
    {
        $result = $amount * $this->secondValueForMathOperations;

        $methodInvocation->replaceArgument("amount", $result);
        return $methodInvocation->proceed();
    }

    /**
     * @return bool
     */
    public function isWasCalled(): bool
    {
        return $this->wasCalled;
    }
}