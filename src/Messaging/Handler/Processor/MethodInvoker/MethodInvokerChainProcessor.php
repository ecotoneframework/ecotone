<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Message;

/**
 * Class MethodInvokerProcessor
 * @package SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MethodInvokerChainProcessor implements MethodInvocation
{
    /**
     * @var MethodCall
     */
    private $methodCall;
    /**
     * @var MethodInvoker
     */
    private $methodInvoker;
    /**
     * @var \ArrayIterator|AroundMethodInterceptor[]
     */
    private $aroundMethodInterceptors;
    /**
     * @var object
     */
    private $interceptedInstance;
    /**
     * @var InterfaceToCall
     */
    private $interceptedInterfaceToCall;
    /**
     * @var Message
     */
    private $requestMessage;
    /**
     * @var object[]
     */
    private $endpointAnnotations;

    /**
     * MethodInvokerProcessor constructor.
     * @param MethodCall $methodCall
     * @param MethodInvoker $methodInvoker
     * @param AroundMethodInterceptor[]|array $aroundMethodInterceptors
     * @param object $interceptedInstance
     * @param InterfaceToCall $interceptedInterfaceToCall
     * @param Message $requestMessage
     * @param object[] $endpointAnnotations
     */
    public function __construct(MethodCall $methodCall, MethodInvoker $methodInvoker, array $aroundMethodInterceptors, $interceptedInstance, InterfaceToCall $interceptedInterfaceToCall, Message $requestMessage, iterable $endpointAnnotations)
    {
        $this->methodCall = $methodCall;
        $this->methodInvoker = $methodInvoker;
        $this->aroundMethodInterceptors = new \ArrayIterator($aroundMethodInterceptors);
        $this->interceptedInstance = $interceptedInstance;
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
            return call_user_func_array([$this->interceptedInstance, $this->interceptedInterfaceToCall->getMethodName()], $this->methodCall->getMethodArgumentValues());
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
        $this->methodCall->replaceArgument($parameterName, $value);
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInstance()
    {
        return $this->interceptedInstance;
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