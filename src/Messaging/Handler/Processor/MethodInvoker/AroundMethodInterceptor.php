<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCallRegistry;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\Support\Assert;

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
    private $interfaceToCall;

    /**
     * MethodInterceptor constructor.
     * @param object $referenceToCall
     * @param InterfaceToCall $interfaceToCall
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function __construct($referenceToCall, InterfaceToCall $interfaceToCall)
    {
        Assert::isObject($referenceToCall, "Method Interceptor should point to instance not class name");
        $this->referenceToCall = $referenceToCall;
        $this->interfaceToCall = $interfaceToCall;
    }

    /**
     * @param $referenceToCall
     * @param string $methodName
     * @param InterfaceToCallRegistry $interfaceToCallRegistry
     * @return AroundMethodInterceptor
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createWith($referenceToCall, string $methodName, InterfaceToCallRegistry $interfaceToCallRegistry) : self
    {
        $interfaceToCall = $interfaceToCallRegistry->getFor($referenceToCall, $methodName);

        return new self($referenceToCall, $interfaceToCall);
    }

    /**
     * @param MethodInvocation $methodInvocation
     * @param MethodCall $methodCall
     * @param Message $requestMessage
     *
     * @return mixed
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function invoke(MethodInvocation $methodInvocation, MethodCall $methodCall, Message $requestMessage)
    {
        $methodInvocationType = TypeDescriptor::create(MethodInvocation::class);

        $hasMethodInvocation = false;
        $argumentsToCallInterceptor = [];
        $interceptedInstanceType = TypeDescriptor::createFromVariable($methodInvocation->getInterceptedInstance());
        $messageType = TypeDescriptor::create(Message::class);

        foreach ($this->interfaceToCall->getInterfaceParameters() as $parameter) {
            $resolvedArgument = null;
            if ($parameter->hasType($methodInvocationType)) {
                $hasMethodInvocation = true;
                $resolvedArgument = $methodInvocation;
            }

            if (!$resolvedArgument) {
                foreach ($methodCall->getMethodArguments() as $methodArgument) {
                    if ($methodArgument->hasSameTypeAs($parameter)) {
                        $resolvedArgument = $methodArgument->value();
                    }
                }

                if (!$resolvedArgument && $parameter->hasType($interceptedInstanceType)) {
                    $resolvedArgument = $methodInvocation->getInterceptedInstance();
                }

                if (!$resolvedArgument && $parameter->hasType($messageType)) {
                    $resolvedArgument = $requestMessage;
                }
            }

            if (!$resolvedArgument && !$parameter->doesAllowNulls()) {
                throw MethodInvocationException::create("{$this->interfaceToCall} can't resolve argument for parameter with name `{$parameter->getName()}`");
            }

            $argumentsToCallInterceptor[] = $resolvedArgument;
        }

        $returnValue = call_user_func_array(
            [$this->referenceToCall, $this->interfaceToCall->getMethodName()],
            $argumentsToCallInterceptor
        );

        if (!$hasMethodInvocation) {
            return $methodInvocation->proceed();
        }

        return $returnValue;
    }
}