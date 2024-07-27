<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Handler\MethodArgument;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class MethodInvocation
 * @package Messaging\Handler\ServiceActivator
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
final class MethodInvoker implements MessageProcessor
{
    /**
     * @param array|ParameterConverter[] $methodParameterConverters
     * @param string[] $methodParameterNames
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public function __construct(private object|string $objectToInvokeOn, private string $objectMethodName, private array $methodParameterConverters, private array $methodParameterNames, private bool $canInterceptorReplaceArguments = false)
    {
        Assert::allInstanceOfType($methodParameterConverters, ParameterConverter::class);
    }

    /**
     * @inheritDoc
     */
    public function executeEndpoint(Message $message)
    {
        $params = $this->getMethodCall($message)->getMethodArgumentValues();

        /** Used direct calls instead of call_user_func to make the stacktrace shorter and more readable, as call_user_func_array add additional stacktrace level */
        if (is_string($this->objectToInvokeOn)) {
            return $this->objectToInvokeOn::{$this->objectMethodName}(...$params);
        }

        return $this->objectToInvokeOn->{$this->objectMethodName}(...$params);
    }

    public function getMethodCall(Message $message): MethodCall
    {
        $methodArguments = [];
        $count = count($this->methodParameterConverters);

        for ($index = 0; $index < $count; $index++) {
            $parameterName = $this->methodParameterNames[$index];
            $data = $this->methodParameterConverters[$index]->getArgumentFrom($message);

            $methodArguments[] = MethodArgument::createWith($parameterName, $data);
        }

        return MethodCall::createWith($methodArguments, $this->canInterceptorReplaceArguments);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $classname = is_object($this->objectToInvokeOn) ? get_class($this->objectToInvokeOn) : $this->objectToInvokeOn;
        return "{$classname}::{$this->objectMethodName}";
    }

    public function getObjectToInvokeOn(): string|object
    {
        return $this->objectToInvokeOn;
    }

    public function getMethodName(): string
    {
        return $this->objectMethodName;
    }
}
