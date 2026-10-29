<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Handler\MethodInvocationException;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Message;
use Throwable;

/**
 * @licence Apache-2.0
 */
final class MethodInvoker implements AroundInterceptable
{
    /**
     * @param ParameterConverter[] $methodParameterConverters
     * @param AroundMethodInterceptor[] $aroundInterceptors
     */
    public function __construct(
        private MethodInvokerObjectResolver $methodInvokerObjectResolver,
        private string $methodName,
        private array $methodParameterConverters,
        private array $methodParameterNames,
        private array $aroundInterceptors = [],
    ) {
    }

    public function execute(Message $message): mixed
    {
        if ($this->aroundInterceptors) {
            return (new AroundMethodInvocation(
                $message,
                $this->aroundInterceptors,
                $this,
            ))->proceed();
        } else {
            $objectToInvokeOn = $this->getObjectToInvokeOn($message);
            return is_string($objectToInvokeOn)
                ? $objectToInvokeOn::{$this->getMethodName()}(...$this->getArguments($message))
                : $objectToInvokeOn->{$this->getMethodName()}(...$this->getArguments($message));
        }
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function getObjectToInvokeOn(Message $message): string|object
    {
        return $this->methodInvokerObjectResolver->resolveFor($message);
    }

    public function getArguments(Message $message): array
    {
        $methodArguments = [];
        $count = count($this->methodParameterConverters);

        for ($index = 0; $index < $count; $index++) {
            $parameterName = $this->methodParameterNames[$index];
            try {
                $data = $this->methodParameterConverters[$index]->getArgumentFrom($message);
            } catch (Throwable $exception) {
                $objectToInvokeOn = $this->getObjectToInvokeOn($message);
                $classToInvokeOn = is_string($objectToInvokeOn) ? $objectToInvokeOn : $objectToInvokeOn::class;
                throw MethodInvocationException::createFromPreviousException(
                    <<<TEXT
                        Cannot resolve parameter '{$parameterName}' while calling {$classToInvokeOn}::{$this->getMethodName()}
                        Reason: {$exception->getMessage()}
                        TEXT,
                    $exception
                );
            }

            $methodArguments[$parameterName] = $data;
        }
        return $methodArguments;
    }
}
