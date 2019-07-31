<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Doctrine\Common\Annotations\AnnotationException;
use ReflectionException;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ReferenceNotFoundException;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\TypeDefinitionException;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Interface MethodInterceptor
 * @package Ecotone\Messaging\MethodInterceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AroundMethodInterceptor
{
    /**
     * @var object
     */
    private $referenceToCall;
    /**
     * @var InterfaceToCall
     */
    private $interceptorInterfaceToCall;
    /**
     * @var ReferenceSearchService
     */
    private $referenceSearchService;

    /**
     * MethodInterceptor constructor.
     * @param object $referenceToCall
     * @param InterfaceToCall $interfaceToCall
     * @param ReferenceSearchService $referenceSearchService
     * @throws MessagingException
     * @throws TypeDefinitionException
     */
    private function __construct($referenceToCall, InterfaceToCall $interfaceToCall, ReferenceSearchService $referenceSearchService)
    {
        Assert::isObject($referenceToCall, "Method Interceptor should point to instance not class name");

        if ($interfaceToCall->hasReturnValue() && !$this->hasMethodInvocationParameter($interfaceToCall)) {
            throw InvalidArgumentException::create("Trying to register {$interfaceToCall} as Around Advice which can return value, but doesn't control invocation using " . MethodInvocation::class . ". Have you wanted to register Before/After Advice or forgot to type hint MethodInvocation?");
        }

        $this->referenceToCall = $referenceToCall;
        $this->interceptorInterfaceToCall = $interfaceToCall;
        $this->referenceSearchService = $referenceSearchService;
    }

    /**
     * @param InterfaceToCall $interfaceToCall
     * @return bool
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    private function hasMethodInvocationParameter(InterfaceToCall $interfaceToCall): bool
    {
        $methodInvocation = TypeDescriptor::create(MethodInvocation::class);
        foreach ($interfaceToCall->getInterfaceParameters() as $interfaceParameter) {
            if ($interfaceParameter->hasType($methodInvocation)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param object $referenceToCall
     * @param string $methodName
     * @param ReferenceSearchService $referenceSearchService
     * @return AroundMethodInterceptor
     * @throws AnnotationException
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws ReferenceNotFoundException
     * @throws ReflectionException
     * @throws TypeDefinitionException
     */
    public static function createWith($referenceToCall, string $methodName, ReferenceSearchService $referenceSearchService): self
    {
        /** @var InterfaceToCallRegistry $interfaceRegistry */
        $interfaceRegistry = $referenceSearchService->get(InterfaceToCallRegistry::REFERENCE_NAME);

        $interfaceToCall = $interfaceRegistry->getFor($referenceToCall, $methodName);

        return new self($referenceToCall, $interfaceToCall, $referenceSearchService);
    }

    /**
     * @param MethodInvocation $methodInvocation
     * @param MethodCall $methodCall
     * @param Message $requestMessage
     *
     * @return mixed
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function invoke(MethodInvocation $methodInvocation, MethodCall $methodCall, Message $requestMessage)
    {
        $methodInvocationType = TypeDescriptor::create(MethodInvocation::class);

        $hasMethodInvocation = false;
        $argumentsToCallInterceptor = [];
        $interceptedInstanceType = TypeDescriptor::createFromVariable($methodInvocation->getObjectToInvokeOn());
        $referenceSearchServiceTypeDescriptor = TypeDescriptor::create(ReferenceSearchService::class);
        $messageType = TypeDescriptor::create(Message::class);
        $messagePayloadType = $requestMessage->getHeaders()->hasContentType() && $requestMessage->getHeaders()->getContentType()->hasTypeParameter()
                                ? $requestMessage->getHeaders()->getContentType()->getTypeParameter()
                                : TypeDescriptor::createFromVariable($requestMessage->getPayload());

        foreach ($this->interceptorInterfaceToCall->getInterfaceParameters() as $parameter) {
            $resolvedArgument = null;

            if (!$resolvedArgument && $parameter->hasType($messagePayloadType)) {
                $resolvedArgument = $requestMessage->getPayload();
            }

            if (!$resolvedArgument && $parameter->hasType($methodInvocationType)) {
                $hasMethodInvocation = true;
                $resolvedArgument = $methodInvocation;
            }

            if (!$resolvedArgument && $parameter->hasType($interceptedInstanceType)) {
                $resolvedArgument = $methodInvocation->getObjectToInvokeOn();
            }

            if (!$resolvedArgument && $parameter->hasType($messageType)) {
                $resolvedArgument = $requestMessage;
            }

            if (!$resolvedArgument) {
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

            if (!$resolvedArgument) {
                if ($parameter->hasType($referenceSearchServiceTypeDescriptor)) {
                    $resolvedArgument = $this->referenceSearchService;
                }
            }

            foreach ($methodCall->getMethodArguments() as $methodArgument) {
                if ($methodArgument->hasSameTypeAs($parameter)) {
                    $resolvedArgument = $methodArgument->value();
                }
            }

            if (!$resolvedArgument && $parameter->getTypeDescriptor()->isNonCollectionArray()) {
                $resolvedArgument = $requestMessage->getHeaders()->headers();
            }

            if (!$resolvedArgument && !$parameter->doesAllowNulls()) {
                throw MethodInvocationException::create("{$this->interceptorInterfaceToCall} can't resolve argument for parameter with name `{$parameter->getName()}`. Maybe your docblock type hint is not correct?");
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
}