<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor;

use PHPUnit\Framework\Assert;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use SimplyCodedSoftware\Messaging\Message;

/**
 * Class StubCallSavingService
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class StubCallSavingService
{
    /**
     * @var bool
     */
    private $wasCalled = false;
    /**
     * @var mixed
     */
    private $valueToReturn;
    /**
     * @var array
     */
    private $argumentsToReplace;


    /**
     * StubCallSavingService constructor.
     * @param $valueToReturn
     */
    private function __construct($valueToReturn)
    {
        $this->valueToReturn = $valueToReturn;
    }

    public static function create() : self
    {
        return new self(null);
    }

    /**
     * @param array $toReplace
     * @return StubCallSavingService
     */
    public static function createWithArgumentsToReplace(array $toReplace) : self
    {
        $self = self::create();
        $self->argumentsToReplace = $toReplace;

        return $self;
    }

    /**
     * @param $valueToReturn
     * @return StubCallSavingService
     */
    public static function createWithReturnType($valueToReturn) : self
    {
        return new self($valueToReturn);
    }

    /**
     * @return bool
     */
    public function callWithReturn()
    {
        return $this->valueToReturn;
    }

    /**
     * @param \stdClass $stdClass
     */
    public function callWithStdClassArgument(\stdClass $stdClass) : void
    {
        $this->wasCalled = true;
    }

    /**
     * @param \stdClass $some
     * @param int $number
     */
    public function callWithStdClassAndIntArgument(\stdClass $some, int $number) : void
    {
        $this->wasCalled = true;
    }

    /**
     * @param \stdClass $some
     * @param int[]|iterable $numbers
     * @param string[]|array $strings
     */
    public function callWithMultipleArguments(\stdClass $some, iterable $numbers, array $strings) : void
    {
        $this->wasCalled = true;
    }

    public function callNoArgumentsAndReturnType() : void
    {
        $this->wasCalled = true;
    }

    public function callWithProceeding(MethodInvocation $methodInvocation) : void
    {
        $methodInvocation->proceed();
        $this->wasCalled = true;
    }

    public function callWithProceedingAndReturning(MethodInvocation $methodInvocation)
    {
        $this->wasCalled = true;

        return $methodInvocation->proceed();
    }

    /**
     * @param MethodInvocation $methodInvocation
     * @return string
     */
    public function callWithEndingChainAndReturning(MethodInvocation $methodInvocation)
    {
        return $this->valueToReturn;
    }

    /**
     * @param MethodInvocation $methodInvocation
     */
    public function callWithEndingChainNoReturning(MethodInvocation $methodInvocation) : void
    {

    }

    /**
     * @param MethodInvocation $methodInvocation
     */
    public function callWithNoReturnType(MethodInvocation $methodInvocation) : void
    {
        return;
    }

    /**
     * @param MethodInvocation $methodInvocation
     * @return mixed
     */
    public function callWithReplacingArguments(MethodInvocation $methodInvocation)
    {
        foreach ($this->argumentsToReplace as $parameterName => $value) {
            $methodInvocation->replaceArgument($parameterName, $value);
        }

        return $methodInvocation->proceed();
    }

    /**
     * @param MethodInvocation $methodInvocation
     * @param \stdClass $stdClass
     * @return mixed
     */
    public function callWithStdClassInvocationArgument(MethodInvocation $methodInvocation, \stdClass $stdClass)
    {
        return $methodInvocation->proceed();
    }

    /**
     * @param MethodInvocation $methodInvocation
     * @param int $test
     * @param \stdClass $stdClass
     * @return mixed
     */
    public function callWithUnorderedClassInvocation(MethodInvocation $methodInvocation, int $test, \stdClass $stdClass)
    {
        return $methodInvocation->proceed();
    }

    /**
     * @param MethodInvocation $methodInvocation
     * @param int[]|iterable $numbers
     * @param string[]|array $strings
     * @param \stdClass $some
     * @return mixed
     */
    public function callMultipleUnorderedArgumentsInvocation(MethodInvocation $methodInvocation, iterable $numbers, array $strings, \stdClass $some)
    {
        return $methodInvocation->proceed();
    }

    public function callWithPassThrough() : void
    {

    }

    public function callWithInterceptedObject(StubCallSavingService $stubCallSavingService)
    {
        return;
    }

    public function callWithRequestMessage(MethodInvocation $methodInvocation, Message $message)
    {
        return $message;
    }

    /**
     * @param \stdClass|null $stdClass
     * @return \stdClass|null
     */
    public function callWithNullableStdClass(MethodInvocation $methodInvocation, ?\stdClass $stdClass)
    {
        return $stdClass;
    }

    /**
     * @return bool
     */
    public function wasCalled() : bool
    {
        return $this->wasCalled;
    }
}