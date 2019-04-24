<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor;

use PHPUnit\Framework\Assert;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\ServiceActivator;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use SimplyCodedSoftware\Messaging\Message;

/**
 * Class StubCallSavingService
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
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

    /**
     * @param MethodInvocation $methodInvocation
     */
    public function callWithNoReturnType(MethodInvocation $methodInvocation) : void
    {
        return;
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

    public function sum(MethodInvocation $methodInvocation, int $amount) : int
    {

    }

    public function multiply(MethodInvocation $methodInvocation, int $amount) : int
    {

    }

    /**
     * @ServiceActivator(inputChannelName="some")
     */
    public function methodWithAnnotation() : void
    {

    }

    public function callWithMessage(Message $message) : void
    {

    }


    public function callWithMessageReturnType(Message $message) : Message
    {
        return $message;
    }

    /**
     * @return bool
     */
    public function wasCalled() : bool
    {
        return $this->wasCalled;
    }
}