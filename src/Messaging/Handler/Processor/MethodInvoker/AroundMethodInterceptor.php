<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker;

use Doctrine\Common\Annotations\AnnotationException;
use ReflectionException;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCallRegistry;
use SimplyCodedSoftware\Messaging\Handler\ReferenceNotFoundException;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessagingException;
use SimplyCodedSoftware\Messaging\Support\Assert;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Interface MethodInterceptor
 * @package SimplyCodedSoftware\Messaging\MethodInterceptor
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
     * @throws TypeDefinitionException
     * @throws MessagingException
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
     * @throws InvalidArgumentException
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws ReferenceNotFoundException
     * @throws MessagingException
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
        $interceptedInstanceType = TypeDescriptor::createFromVariable($methodInvocation->getInterceptedInstance());
        $referenceSearchServiceTypeDescriptor = TypeDescriptor::create(ReferenceSearchService::class);
        $messageType = TypeDescriptor::create(Message::class);

        foreach ($this->interceptorInterfaceToCall->getInterfaceParameters() as $parameter) {
            $resolvedArgument = null;

            foreach ($methodCall->getMethodArguments() as $methodArgument) {
                if ($methodArgument->hasSameTypeAs($parameter)) {
                    $resolvedArgument = $methodArgument->value();
                }
            }

            if (!$resolvedArgument && $parameter->hasType($methodInvocationType)) {
                $hasMethodInvocation = true;
                $resolvedArgument = $methodInvocation;
            }

            if (!$resolvedArgument && $parameter->hasType($interceptedInstanceType)) {
                $resolvedArgument = $methodInvocation->getInterceptedInstance();
            }

            if (!$resolvedArgument && $parameter->hasType($messageType)) {
                $resolvedArgument = $requestMessage;
            }

            if (!$resolvedArgument) {
                if ($methodInvocation->getInterceptedInterface()->hasMethodAnnotation($parameter->getTypeDescriptor())) {
                    $resolvedArgument = $methodInvocation->getInterceptedInterface()->getMethodAnnotation($parameter->getTypeDescriptor());
                }
                if ($methodInvocation->getInterceptedInterface()->hasClassAnnotation($parameter->getTypeDescriptor())) {
                    $resolvedArgument = $methodInvocation->getInterceptedInterface()->getClassAnnotation($parameter->getTypeDescriptor());
                }
            }

            if (!$resolvedArgument) {
                foreach ($methodInvocation->getEndpointAnnotations() as $endpointAnnotation) {
                    if (TypeDescriptor::createFromVariable($endpointAnnotation)->equals($parameter->getTypeDescriptor())) {
                        $resolvedArgument = $endpointAnnotation;
                        break;
                    }
                }
            }

            if (!$resolvedArgument) {
                if ($parameter->hasType($referenceSearchServiceTypeDescriptor)) {
                    $resolvedArgument = $this->referenceSearchService;
                }
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