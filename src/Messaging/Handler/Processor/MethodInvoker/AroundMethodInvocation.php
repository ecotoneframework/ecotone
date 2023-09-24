<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use ArrayIterator;
use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Message;

/**
 * Executes endpoint with around interceptors
 *
 * Class MethodInvokerProcessor
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AroundMethodInvocation implements MethodInvocation
{
    /**
     * @var ArrayIterator|AroundMethodInterceptor[]
     */
    private iterable $aroundMethodInterceptors;

    private MethodCall $methodCall;

    public function __construct(
        private Message          $requestMessage,
        array                    $aroundMethodInterceptors,
        private MessageProcessor $interceptedMessageProcessor,
    ) {
        $this->aroundMethodInterceptors = new ArrayIterator($aroundMethodInterceptors);
        $this->methodCall = $interceptedMessageProcessor->getMethodCall($requestMessage);
    }

    /**
     * @inheritDoc
     */
    public function proceed()
    {
        /** @var AroundMethodInterceptor $aroundMethodInterceptor */
        $aroundMethodInterceptor = $this->aroundMethodInterceptors->current();
        $this->aroundMethodInterceptors->next();

        if (! $aroundMethodInterceptor) {
            return $this->interceptedMessageProcessor->executeEndpoint($this->requestMessage);
        }

        return $aroundMethodInterceptor->invoke(
            $this,
            $this->requestMessage
        );
    }

    /**
     * @return array
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
        return $this->interceptedMessageProcessor->getObjectToInvokeOn();
    }

    /**
     * @param string $parameterName
     * @param mixed $value
     * @return void
     */
    public function replaceArgument(string $parameterName, $value): void
    {
        $this->methodCall->replaceArgument($parameterName, $value);
    }
}
