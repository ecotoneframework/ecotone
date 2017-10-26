<?php

namespace Messaging\Handler\Processor;
use Messaging\Handler\MessageProcessor;
use Messaging\Message;
use Messaging\Support\Assert;
use Messaging\Support\InvalidArgumentException;

/**
 * Class MethodInvocation
 * @package Messaging\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class MethodInvocation implements MessageProcessor
{
    /**
     * @var object
     */
    private $objectToInvokeOn;
    /**
     * @var string
     */
    private $objectMethodName;
    /**
     * @var MethodArgument[]
     */
    private $orderedMethodArguments;

    /**
     * MethodInvocation constructor.
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @param array|MethodArgument[] $methodArguments
     */
    private function __construct($objectToInvokeOn, string $objectMethodName, array $methodArguments)
    {
        Assert::isObject($objectToInvokeOn, "Passed value for invocation is not an object");
        Assert::allInstanceOfType($methodArguments, MethodArgument::class);

        $this->init($objectToInvokeOn, $objectMethodName, $methodArguments);
    }

    /**
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @param array $methodArguments
     * @return MethodInvocation
     */
    public static function createWith($objectToInvokeOn, string $objectMethodName, array $methodArguments) : self
    {
        return new self($objectToInvokeOn, $objectMethodName, $methodArguments);
    }

    /**
     * @inheritDoc
     */
    public function processMessage(Message $message)
    {
        return call_user_func_array([$this->objectToInvokeOn, $this->objectMethodName], $this->getMethodArguments($message));
    }

    /**
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @param array|MethodArgument[] $methodArguments
     * @throws \Messaging\MessagingException
     */
    private function init($objectToInvokeOn, string $objectMethodName, array $methodArguments) : void
    {
        if (!$this->hasObjectMethod($objectToInvokeOn, $objectMethodName)) {
            throw InvalidArgumentException::create("Object {$this->objectToClassName($objectToInvokeOn)} does not contain method {$objectMethodName}");
        }

        $objectToInvokeReflection = new \ReflectionMethod($objectToInvokeOn, $objectMethodName);
        $objectToInvokeArguments = $objectToInvokeReflection->getParameters();
        $passedArgumentsCount = count($methodArguments);
        $requiredArgumentsCount = count($objectToInvokeReflection->getParameters());

        if ($this->canBeInvokedWithDefaultPayloadArgument($passedArgumentsCount, $requiredArgumentsCount)) {
            $methodArguments = [PayloadArgument::create($objectToInvokeArguments[0]->getName())];
            $passedArgumentsCount = 1;
        }

        if (!$this->hasEnoughArguments($passedArgumentsCount, $requiredArgumentsCount)) {
            throw InvalidArgumentException::create("Object {$this->objectToClassName($objectToInvokeOn)} requires {$requiredArgumentsCount}, but passed {$passedArgumentsCount}");
        }

        $orderedMethodArguments = [];
        foreach ($objectToInvokeArguments as $invokeArgument) {
            $orderedMethodArguments[] = $this->getMethodArgumentForInvokedOne($this->objectToClassName($objectToInvokeOn), $objectMethodName, $invokeArgument, $methodArguments);
        }

        $this->objectToInvokeOn = $objectToInvokeOn;
        $this->objectMethodName = $objectMethodName;
        $this->orderedMethodArguments = $orderedMethodArguments;
    }

    /**
     * @param Message $message
     * @return array
     */
    private function getMethodArguments(Message $message) : array
    {
        $methodArguments = [];

        foreach ($this->orderedMethodArguments as $methodArgument) {
            $methodArguments[] = $methodArgument->getFrom($message);
        }

        return $methodArguments;
    }

    /**
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @return bool
     */
    private function hasObjectMethod($objectToInvokeOn, string $objectMethodName): bool
    {
        return method_exists($objectToInvokeOn, $objectMethodName);
    }

    /**
     * @param $objectToInvokeOn
     * @return string
     */
    private function objectToClassName($objectToInvokeOn): string
    {
        return get_class($objectToInvokeOn);
    }

    /**
     * @param int $passedArgumentsCount
     * @param int $requiredArgumentsCount
     * @return bool
     */
    private function hasEnoughArguments(int $passedArgumentsCount, int $requiredArgumentsCount): bool
    {
        return $passedArgumentsCount === $requiredArgumentsCount;
    }

    /**
     * @param $requiredArgumentsCount
     * @param $passedArgumentsCount
     * @return bool
     */
    private function canBeInvokedWithDefaultPayloadArgument(int $passedArgumentsCount, int $requiredArgumentsCount): bool
    {
        return $requiredArgumentsCount === 1 && $passedArgumentsCount === 0;
    }

    /**
     * @param string $invokedClass
     * @param string $methodToInvoke
     * @param \ReflectionParameter $invokeArgument
     * @param array|MethodArgument[] $methodArguments
     * @return MethodArgument
     * @throws \Messaging\MessagingException
     */
    private function getMethodArgumentForInvokedOne(string $invokedClass, string $methodToInvoke, \ReflectionParameter $invokeArgument, array $methodArguments): MethodArgument
    {
        $lastArgumentName = '';
        foreach ($methodArguments as $methodArgument) {
            $lastArgumentName = $methodArgument->getName();
            if ($invokeArgument->getName() == $lastArgumentName) {
                return $methodArgument;
            }
        }

        throw InvalidArgumentException::create("Invoked object {$invokedClass} with method {$methodToInvoke} does not have argument with name {$lastArgumentName}");
    }

    public function __toString()
    {
        $objectToInvokeOn = get_class($this->objectToInvokeOn);

        return "Object {$objectToInvokeOn}, method {$this->objectMethodName}";
    }
}