<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\ReferenceSearchService;
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
        if ($interfaceToCall->canReturnValue() && !$this->hasMethodInvocationParameter($interfaceToCall)) {
            throw InvalidArgumentException::create("Trying to register {$interfaceToCall} as Around Advice which can return value, but doesn't control invocation using " . MethodInvocation::class . ". Have you wanted to register Before/After Advice or forgot to type hint MethodInvocation?");
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

    /**
     * @param MethodInvocation $methodInvocation
     * @param MethodCall       $methodCall
     * @param Message          $requestMessage
     *
     * @return mixed
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function invoke(MethodInvocation $methodInvocation, MethodCall $methodCall, Message $requestMessage)
    {
        $methodInvocationType = TypeDescriptor::create(MethodInvocation::class);

        $hasMethodInvocation                  = false;
        $argumentsToCallInterceptor           = [];
        $interceptedInstanceType              = is_string($methodInvocation->getObjectToInvokeOn()) ? TypeDescriptor::create($methodInvocation->getObjectToInvokeOn()) : TypeDescriptor::createFromVariable($methodInvocation->getObjectToInvokeOn());
        $referenceSearchServiceTypeDescriptor = TypeDescriptor::create(ReferenceSearchService::class);
        $messageType                          = TypeDescriptor::create(Message::class);
        $messagePayloadType                   = $requestMessage->getHeaders()->hasContentType() && $requestMessage->getHeaders()->getContentType()->hasTypeParameter()
            ? $requestMessage->getHeaders()->getContentType()->getTypeParameter()
            : TypeDescriptor::createFromVariable($requestMessage->getPayload());

        foreach ($this->interceptorInterfaceToCall->getInterfaceParameters() as $parameter) {
            $resolvedArgument = null;
            foreach ($this->parameterConverters as $parameterConverter) {
                if ($parameterConverter->isHandling($parameter)) {
                    $resolvedArgument = $parameterConverter->getArgumentFrom($this->interceptorInterfaceToCall, $parameter, $requestMessage, []);
                }
            }

            if (is_null($resolvedArgument) && $parameter->canBePassedIn($messagePayloadType)) {
                $resolvedArgument = $requestMessage->getPayload();
            }

            if (is_null($resolvedArgument) && $parameter->canBePassedIn($methodInvocationType)) {
                $hasMethodInvocation = true;
                $resolvedArgument    = $methodInvocation;
            }

            if (is_null($resolvedArgument) && $parameter->canBePassedIn($interceptedInstanceType)) {
                $resolvedArgument = is_string($methodInvocation->getObjectToInvokeOn()) ? null : $methodInvocation->getObjectToInvokeOn();
            }

            if (is_null($resolvedArgument) && $parameter->canBePassedIn($messageType)) {
                $resolvedArgument = $requestMessage;
            }

            if (is_null($resolvedArgument)) {
                if ($methodInvocation->getInterceptedInterface()->hasClassAnnotation($parameter->getTypeDescriptor())) {
                    $resolvedArgument = $methodInvocation->getInterceptedInterface()->getClassAnnotation($parameter->getTypeDescriptor());
                }
                if ($methodInvocation->getInterceptedInterface()->hasMethodAnnotation($parameter->getTypeDescriptor())) {
                    $resolvedArgument = $methodInvocation->getInterceptedInterface()->getMethodAnnotation($parameter->getTypeDescriptor());
                }
                foreach ($methodInvocation->getEndpointAnnotations() as $endpointAnnotation) {
                    if (TypeDescriptor::createFromVariable($endpointAnnotation)->equals($parameter->getTypeDescriptor())) {
                        $resolvedArgument = $endpointAnnotation;
                    }
                }
            }

            if (is_null($resolvedArgument)) {
                if ($parameter->canBePassedIn($referenceSearchServiceTypeDescriptor)) {
                    $resolvedArgument = $this->referenceSearchService;
                }
            }

            foreach ($methodCall->getMethodArguments() as $methodArgument) {
                if ($methodArgument->hasEqualTypeAs($parameter)) {
                    $resolvedArgument = $methodArgument->value();
                }
            }

            if (is_null($resolvedArgument) && $parameter->getTypeDescriptor()->isNonCollectionArray()) {
                $resolvedArgument = $requestMessage->getHeaders()->headers();
            }

            if (is_null($resolvedArgument) && !$parameter->doesAllowNulls()) {
                throw MethodInvocationException::create("{$this->interceptorInterfaceToCall} can't resolve argument for parameter with name `{$parameter->getName()}`. It can be that the value is null in this scenario (for example type hinting for Aggregate, when calling Aggregate Factory Method), however the interface does not allow for nulls.");
            }

            $argumentsToCallInterceptor[] = $resolvedArgument;
        }

        $returnValue = call_user_func_array(
            [$this->referenceToCall, $this->interceptorInterfaceToCall->getMethodName()],
            $argumentsToCallInterceptor
        );

        if (!$hasMethodInvocation) {
            return $methodInvocation->proceed();
        }

        return $returnValue;
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