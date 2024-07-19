<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class AroundMethodInterceptor
{
    /**
     * @param ParameterConverter[] $parameterConverters
     */
    public function __construct(private object $referenceToCall, private InterfaceToCall $interceptorInterfaceToCall, private array $parameterConverters, private bool $hasMethodInvocation)
    {
        if ($interceptorInterfaceToCall->canReturnValue() && ! $this->hasMethodInvocation) {
            throw InvalidArgumentException::create("Trying to register {$interceptorInterfaceToCall} as Around Advice which can return value, but doesn't control invocation using " . MethodInvocation::class . '. Have you wanted to register Before/After Advice or forgot to type hint MethodInvocation?');
        }
    }

    /**
     * @param ParameterConverter[] $parameterConverters
     */
    public static function createWith(object $referenceToCall, string $methodName, ReferenceSearchService $referenceSearchService, array $parameterConverters, bool $hasMethodInvocation): self
    {
        /** @var InterfaceToCallRegistry $interfaceRegistry */
        $interfaceRegistry = $referenceSearchService->get(InterfaceToCallRegistry::REFERENCE_NAME);

        $interfaceToCall = $interfaceRegistry->getFor($referenceToCall, $methodName);

        return new self($referenceToCall, $interfaceToCall, $parameterConverters, $hasMethodInvocation);
    }

    public function invoke(MethodInvocation $methodInvocation, Message $requestMessage)
    {
        $argumentsToCall           = [];

        $count = count($this->parameterConverters);
        for ($index = 0; $index < $count; $index++) {
            $argumentsToCall[] = $this->parameterConverters[$index]->getArgumentFrom(
                $requestMessage,
                $methodInvocation,
            );
        }

        $returnValue = $this->referenceToCall->{$this->interceptorInterfaceToCall->getMethodName()}(...$argumentsToCall);

        if (! $this->hasMethodInvocation) {
            return $methodInvocation->proceed();
        }

        return $returnValue;
    }
}
