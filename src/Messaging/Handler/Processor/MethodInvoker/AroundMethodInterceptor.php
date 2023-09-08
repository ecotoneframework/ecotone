<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDefinitionException;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Interface MethodInterceptor
 * @package Ecotone\Messaging\MethodInterceptor
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AroundMethodInterceptor
{
    private object $referenceToCall;
    private InterfaceToCall $interceptorInterfaceToCall;
    private ReferenceSearchService $referenceSearchService;
    /** @var ParameterConverter[] */
    private array $parameterConverters;


    private function __construct(object $referenceToCall, InterfaceToCall $interfaceToCall, ReferenceSearchService $referenceSearchService, array $parameterConverters)
    {
        if ($interfaceToCall->canReturnValue() && ! $this->hasMethodInvocationParameter($interfaceToCall)) {
            throw InvalidArgumentException::create("Trying to register {$interfaceToCall} as Around Advice which can return value, but doesn't control invocation using " . MethodInvocation::class . '. Have you wanted to register Before/After Advice or forgot to type hint MethodInvocation?');
        }

        $this->referenceToCall            = $referenceToCall;
        $this->interceptorInterfaceToCall = $interfaceToCall;
        $this->referenceSearchService     = $referenceSearchService;
        $this->parameterConverters = $parameterConverters;
    }

    /**
     * @var ParameterConverter[] $parameterConverters
     */
    public static function createWith(object $referenceToCall, string $methodName, ReferenceSearchService $referenceSearchService, array $parameterConverters): self
    {
        /** @var InterfaceToCallRegistry $interfaceRegistry */
        $interfaceRegistry = $referenceSearchService->get(InterfaceToCallRegistry::REFERENCE_NAME);

        $interfaceToCall = $interfaceRegistry->getFor($referenceToCall, $methodName);

        return new self($referenceToCall, $interfaceToCall, $referenceSearchService, $parameterConverters);
    }

    public function invoke(MethodInvocation $methodInvocation, MethodCall $methodCall, Message $requestMessage)
    {
        $methodInvocationType = TypeDescriptor::create(MethodInvocation::class);

        $hasMethodInvocation                  = false;
        $argumentsToCall           = [];
        $messagePayloadType                   = $requestMessage->getHeaders()->hasContentType() && $requestMessage->getHeaders()->getContentType()->hasTypeParameter()
            ? $requestMessage->getHeaders()->getContentType()->getTypeParameter()
            : TypeDescriptor::createFromVariable($requestMessage->getPayload());

        foreach ($this->interceptorInterfaceToCall->getInterfaceParameters() as $parameter) {
            if ($parameter->canBePassedIn($methodInvocationType)) {
                $hasMethodInvocation = true;
            }
        }

        foreach ($this->interceptorInterfaceToCall->getInterfaceParameters() as $parameter) {
            $argumentsToCall[] = $this->resolveArgument($parameter, $methodInvocation, $methodCall, $requestMessage, $messagePayloadType);
        }

        $returnValue = $this->referenceToCall->{$this->interceptorInterfaceToCall->getMethodName()}(...$argumentsToCall);

        if (! $hasMethodInvocation) {
            return $methodInvocation->proceed();
        }

        return $returnValue;
    }

    private function resolveArgument(
        InterfaceParameter $parameter,
        MethodInvocation $methodInvocation,
        MethodCall $methodCall,
        Message $requestMessage,
        Type $messagePayloadType
    ): mixed {
        foreach ($this->parameterConverters as $parameterConverter) {
            if ($parameterConverter->isHandling($parameter)) {
                return $parameterConverter->getArgumentFrom($this->interceptorInterfaceToCall, $parameter, $requestMessage, []);
            }
        }

        if ($parameter->canBePassedIn($messagePayloadType)) {
            return $requestMessage->getPayload();
        }

        if ($parameter->canBePassedIn(TypeDescriptor::create(MethodInvocation::class))) {
            return $methodInvocation;
        }

        if (
            $parameter->canBePassedIn($methodInvocation->getInterceptedInterface()->getInterfaceType())
            && ! is_string($methodInvocation->getObjectToInvokeOn())) {
            return $methodInvocation->getObjectToInvokeOn();
        }

        if ($parameter->canBePassedIn(TypeDescriptor::create(Message::class))) {
            return $requestMessage;
        }

        if ($methodInvocation->getInterceptedInterface()->hasClassAnnotation($parameter->getTypeDescriptor())) {
            return $methodInvocation->getInterceptedInterface()->getClassAnnotation($parameter->getTypeDescriptor());
        }
        if ($methodInvocation->getInterceptedInterface()->hasMethodAnnotation($parameter->getTypeDescriptor())) {
            return $methodInvocation->getInterceptedInterface()->getMethodAnnotation($parameter->getTypeDescriptor());
        }
        foreach ($methodInvocation->getEndpointAnnotations() as $endpointAnnotation) {
            if (TypeDescriptor::createFromVariable($endpointAnnotation)->equals($parameter->getTypeDescriptor())) {
                return $endpointAnnotation;
            }
        }
        foreach ($methodInvocation->getEndpointAnnotations() as $endpointAnnotation) {
            if (TypeDescriptor::createFromVariable($endpointAnnotation)->isCompatibleWith($parameter->getTypeDescriptor())) {
                return $endpointAnnotation;
            }
        }

        if ($parameter->canBePassedIn(TypeDescriptor::create(ReferenceSearchService::class))) {
            return $this->referenceSearchService;
        }

        foreach ($methodCall->getMethodArguments() as $methodArgument) {
            if ($methodArgument->hasEqualTypeAs($parameter)) {
                return $methodArgument->value();
            }
        }

        if ($parameter->getTypeDescriptor()->isNonCollectionArray()) {
            return $requestMessage->getHeaders()->headers();
        }

        if ($parameter->doesAllowNulls()) {
            return null;
        }
        throw MethodInvocationException::create("{$this->interceptorInterfaceToCall} can't resolve argument for parameter with name `{$parameter->getName()}`. It can be that the value is null in this scenario (for example type hinting for Aggregate, when calling Aggregate Factory Method), however the interface does not allow for nulls.");
    }

    /**
     * @param InterfaceToCall $interfaceToCall
     *
     * @return bool
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    private function hasMethodInvocationParameter(InterfaceToCall $interfaceToCall): bool
    {
        $methodInvocation = TypeDescriptor::create(MethodInvocation::class);
        foreach ($interfaceToCall->getInterfaceParameters() as $interfaceParameter) {
            if ($interfaceParameter->canBePassedIn($methodInvocation)) {
                return true;
            }
        }

        return false;
    }
}
