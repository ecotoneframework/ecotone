<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageProcessor;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Class MethodInvocation
 * @package Messaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class MethodInvoker implements MessageProcessor
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
     * @var MessageToParameterConverter[]
     */
    private $orderedMethodArguments;
    /**
     * @var bool
     */
    private $isCalledStatically;

    /**
     * MethodInvocation constructor.
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @param array|MessageToParameterConverter[] $methodParameterConverters
     */
    private function __construct($objectToInvokeOn, string $objectMethodName, array $methodParameterConverters)
    {
        Assert::allInstanceOfType($methodParameterConverters, MessageToParameterConverter::class);

        $this->init($objectToInvokeOn, $objectMethodName, $methodParameterConverters);
    }

    /**
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @param array $methodArguments
     * @return self
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
     * @param array|MessageToParameterConverter[] $methodParameterConverters
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function init($objectToInvokeOn, string $objectMethodName, array $methodParameterConverters) : void
    {
        $this->isCalledStatically = false;
        if (!is_object($objectToInvokeOn)) {
            $interfaceToCall = InterfaceToCall::create($objectToInvokeOn, $objectMethodName);

            if (!$interfaceToCall->isStaticallyCalled()) {
                throw InvalidArgumentException::create("Reference to invoke must be object given {$objectToInvokeOn}");
            }
            $this->isCalledStatically = true;
        }

        if (!$this->hasObjectMethod($objectToInvokeOn, $objectMethodName)) {
            throw InvalidArgumentException::create("Object {$this->objectToClassName($objectToInvokeOn)} does not contain method {$objectMethodName}");
        }

        $objectToInvokeReflection = new \ReflectionMethod($objectToInvokeOn, $objectMethodName);
        $parametersForObjectToInvoke = $objectToInvokeReflection->getParameters();
        $passedArgumentsCount = count($methodParameterConverters);
        $requiredArgumentsCount = count($objectToInvokeReflection->getParameters());

        if ($this->canBeInvokedWithDefaultArgument($passedArgumentsCount, $requiredArgumentsCount)) {
            $firstArgument = $parametersForObjectToInvoke[0];

            if ((string)$firstArgument->getType() === Message::class) {
                $methodParameterConverters = [MessageParameterConverter::create($firstArgument->getName())];
            }else {
                $methodParameterConverters = [MessageToPayloadParameterConverter::create($firstArgument->getName())];
            }

            $passedArgumentsCount = 1;
        }

        if (!$this->hasEnoughArguments($passedArgumentsCount, $requiredArgumentsCount)) {
            throw InvalidArgumentException::create("Object {$this->objectToClassName($objectToInvokeOn)} requires {$requiredArgumentsCount}, but passed {$passedArgumentsCount}");
        }

        $orderedMethodArguments = [];
        foreach ($parametersForObjectToInvoke as $invokeParameter) {
            $orderedMethodArguments[] = $this->getMethodArgumentFor($this->objectToClassName($objectToInvokeOn), $objectMethodName, $invokeParameter, $methodParameterConverters);
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
            $methodArguments[] = $methodArgument->getArgumentFrom($message);
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
        return $this->isCalledStatically ? $objectToInvokeOn : get_class($objectToInvokeOn);
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
    private function canBeInvokedWithDefaultArgument(int $passedArgumentsCount, int $requiredArgumentsCount): bool
    {
        return $requiredArgumentsCount === 1 && $passedArgumentsCount === 0;
    }

    /**
     * @param string $invokedClass
     * @param string $methodToInvoke
     * @param \ReflectionParameter $invokeParameter
     * @param array|MessageToParameterConverter[] $methodParameterConverters
     * @return MessageToParameterConverter
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function getMethodArgumentFor(string $invokedClass, string $methodToInvoke, \ReflectionParameter $invokeParameter, array $methodParameterConverters): MessageToParameterConverter
    {
        foreach ($methodParameterConverters as $methodParameterConverter) {
            if ($methodParameterConverter->isHandling($invokeParameter)) {
                return $methodParameterConverter;
            }
        }

        throw InvalidArgumentException::create("Invoked object {$invokedClass} with method {$methodToInvoke} has no converter for {$invokeParameter->getName()}");
    }

    public function __toString()
    {
        $objectToInvokeOn = get_class($this->objectToInvokeOn);

        return "Object {$objectToInvokeOn}, method {$this->objectMethodName}";
    }
}