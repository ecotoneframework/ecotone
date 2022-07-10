<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class MethodInvokerProcessor
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MethodInvokerChainProcessor implements MethodInvocation
{
    private MethodCall $methodCall;
    private MethodInvoker $methodInvoker;
    /**
     * @var \ArrayIterator|AroundMethodInterceptor[]
     */
    private iterable $aroundMethodInterceptors;
    /**
     * @var object|string
     */
    private $objectToInvokeOn;
    private InterfaceToCall $interceptedInterfaceToCall;
    private Message $requestMessage;
    /**
     * @var object[]
     */
    private array $endpointAnnotations;

    /**
     * @param object[] $endpointAnnotations
     */
    public function __construct(MethodCall $methodCall, MethodInvoker $methodInvoker, array $aroundMethodInterceptors, $objectToInvokeOn, InterfaceToCall $interceptedInterfaceToCall, Message $requestMessage, iterable $endpointAnnotations)
    {
        $this->methodCall = $methodCall;
        $this->methodInvoker = $methodInvoker;
        $this->aroundMethodInterceptors = new \ArrayIterator($aroundMethodInterceptors);
        $this->objectToInvokeOn = $objectToInvokeOn;
        $this->interceptedInterfaceToCall = $interceptedInterfaceToCall;
        $this->requestMessage = $requestMessage;
        $this->endpointAnnotations = $endpointAnnotations;
    }


    /**
     * @inheritDoc
     */
    public function proceed()
    {
        /** @var AroundMethodInterceptor $aroundMethodInterceptor */
        $aroundMethodInterceptor = $this->aroundMethodInterceptors->current();
        $this->aroundMethodInterceptors->next();

        if (!$aroundMethodInterceptor) {
            return call_user_func_array([$this->objectToInvokeOn, $this->interceptedInterfaceToCall->getMethodName()], $this->methodCall->getMethodArgumentValues());
        }

        return $aroundMethodInterceptor->invoke(
            $this,
            $this->methodCall,
            $this->requestMessage
        );
    }

    /**
     * @inheritDoc
     */
    public function replaceArgument(string $parameterName, $value): void
    {
        if (!$this->methodCall->hasMethodArgumentWithName($parameterName)) {
            throw InvalidArgumentException::create("Can't replace argument with parameter name {$parameterName}. This parameter does not exists for {$this->getInterceptedInterface()}");
        }

        $this->methodCall->replaceArgument($parameterName, $value);
    }

    /**
     * @inheritDoc
     */
    public function getArguments(): array
    {
        return $this->methodCall->getMethodArgumentValues();
    }

    /**
     * @var string|object
     */
    public function getObjectToInvokeOn()
    {
        return $this->objectToInvokeOn;
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedClassName(): string
    {
        return $this->interceptedInterfaceToCall->getInterfaceName();
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedMethodName(): string
    {
        return $this->interceptedInterfaceToCall->getMethodName();
    }

    /**
     * @inheritdoc
     */
    public function getInterceptedInterface() : InterfaceToCall
    {
        return $this->interceptedInterfaceToCall;
    }

    /**
     * @inheritDoc
     */
    public function getEndpointAnnotations(): iterable
    {
        return $this->endpointAnnotations;
    }
}