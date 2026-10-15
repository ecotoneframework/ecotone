<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use function array_values;

use Ecotone\Api\Interceptor\MethodInvocation;
use Ecotone\Api\Messaging\Message;

/**
 * Executes endpoint with around interceptors
 *
 * Class MethodInvokerProcessor
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class AroundMethodInvocation implements MethodInvocation
{
    /**
     * @var AroundMethodInterceptor[]
     */
    private iterable $aroundMethodInterceptors;

    private array $arguments;

    /**
     * @param AroundMethodInterceptor[] $aroundMethodInterceptors
     */
    public function __construct(
        private Message $requestMessage,
        array $aroundMethodInterceptors,
        private AroundInterceptable $interceptedMethodInvocation,
        private int $currentExecutionIndex = 0
    ) {
        $this->aroundMethodInterceptors = $aroundMethodInterceptors;
        $this->arguments = $interceptedMethodInvocation->getArguments($this->requestMessage);
    }

    public function cloneCurrentState(): self
    {
        return new self(
            $this->requestMessage,
            $this->aroundMethodInterceptors,
            $this->interceptedMethodInvocation,
            $this->currentExecutionIndex,
        );
    }

    /**
     * @inheritDoc
     */
    public function proceed(): mixed
    {
        do {
            /** @var AroundMethodInterceptor $aroundMethodInterceptor */
            $aroundMethodInterceptor = $this->aroundMethodInterceptors[$this->currentExecutionIndex] ?? null;
            $this->currentExecutionIndex++;

            if (! $aroundMethodInterceptor) {
                $objectToInvokeOn = $this->getObjectToInvokeOn();
                return is_string($objectToInvokeOn)
                    ? $objectToInvokeOn::{$this->getMethodName()}(...$this->arguments)
                    : $objectToInvokeOn->{$this->getMethodName()}(...$this->arguments);
            }

            $arguments = $aroundMethodInterceptor->getArguments(
                $this,
                $this->requestMessage
            );
            $referenceToCall = $aroundMethodInterceptor->getReferenceToCall();
            $methodName = $aroundMethodInterceptor->getMethodName();

            $returnValue = $referenceToCall->{$methodName}(...$arguments);
        } while (! $aroundMethodInterceptor->hasMethodInvocation());

        return $returnValue;
    }

    /**
     * @return mixed[]
     */
    public function getArguments(): array
    {
        return array_values($this->arguments);
    }

    public function getObjectToInvokeOn(): string|object
    {
        return $this->interceptedMethodInvocation->getObjectToInvokeOn($this->requestMessage);
    }

    public function getMethodName(): string
    {
        return $this->interceptedMethodInvocation->getMethodName();
    }

    public function getName(): string
    {
        $object = $this->getObjectToInvokeOn();
        $classname = is_string($object) ? $object : get_class($object);
        return "{$classname}::{$this->getMethodName()}";
    }
}
