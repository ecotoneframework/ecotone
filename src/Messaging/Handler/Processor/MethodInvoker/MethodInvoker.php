<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Handler\MethodArgument;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class MethodInvocation
 * @package Messaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class MethodInvoker implements MessageProcessor
{
    private string|object $objectToInvokeOn;
    private string $objectMethodName;
    /**
     * @var ParameterConverter[]
     */
    private array $orderedMethodArguments;
    private InterfaceToCall $interfaceToCall;
    private bool $canInterceptorReplaceArguments;

    /**
     * MethodInvocation constructor.
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @param array|ParameterConverter[] $methodParameterConverters
     * @param InterfaceToCall $interfaceToCall
     * @param bool $canInterceptorReplaceArguments
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    private function __construct($objectToInvokeOn, string $objectMethodName, array $methodParameterConverters, InterfaceToCall $interfaceToCall, bool $canInterceptorReplaceArguments)
    {
        Assert::allInstanceOfType($methodParameterConverters, ParameterConverter::class);

        $this->orderedMethodArguments = $methodParameterConverters;
        $this->objectToInvokeOn = $objectToInvokeOn;
        $this->objectMethodName = $objectMethodName;
        $this->interfaceToCall = $interfaceToCall;
        $this->canInterceptorReplaceArguments = $canInterceptorReplaceArguments;
    }

    /**
     * @param ParameterConverterBuilder[] $methodParametersConverterBuilders
     */
    public static function createWith(InterfaceToCall $interfaceToCall, $objectToInvokeOn, array $methodParametersConverterBuilders, ReferenceSearchService $referenceSearchService, array $endpointAnnotations = []): self
    {
        $methodParametersConverterBuilders = MethodArgumentsFactory::createDefaultMethodParameters($interfaceToCall, $methodParametersConverterBuilders, $endpointAnnotations, null, false);
        $methodParameterConverters         = [];
        foreach ($methodParametersConverterBuilders as $index => $methodParameter) {
            $methodParameterConverters[] = $methodParameter->build($referenceSearchService, $interfaceToCall, $interfaceToCall->getParameterAtIndex($index));
        }

        return new self($objectToInvokeOn, $interfaceToCall->getMethodName(), $methodParameterConverters, $interfaceToCall, true);
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
        $count = count($this->orderedMethodArguments);

        for ($index = 0; $index < $count; $index++) {
            $interfaceParameter = $this->interfaceToCall->getParameterAtIndex($index);
            $data = $this->orderedMethodArguments[$index]->getArgumentFrom($message);

            $methodArguments[] = MethodArgument::createWith($interfaceParameter, $data);
        }

        return MethodCall::createWith($methodArguments, $this->canInterceptorReplaceArguments);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->interfaceToCall;
    }

    public function getObjectToInvokeOn(): string|object
    {
        return $this->objectToInvokeOn;
    }

    public function getInterfaceToCall(): InterfaceToCall
    {
        return $this->interfaceToCall;
    }
}
