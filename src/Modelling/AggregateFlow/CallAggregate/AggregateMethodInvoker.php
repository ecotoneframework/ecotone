<?php

namespace Ecotone\Modelling\AggregateFlow\CallAggregate;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundMethodInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundMethodInvocation;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateMessage;

class AggregateMethodInvoker
{
    /**
     * @param array<ParameterConverter> $methodParameterConverters
     * @param array<string> $methodParameterNames
     * @param array<AroundMethodInterceptor> $aroundMethodInterceptors
     */
    public function __construct(
        private string $aggregateClass,
        private string $objectMethodName,
        private ?Type $returnType,
        private array $methodParameterNames,
        private bool $isCommandHandler,
        private array $methodParameterConverters,
        private array $aroundMethodInterceptors,
    ) {
        Assert::allInstanceOfType($methodParameterConverters, ParameterConverter::class);
        Assert::allInstanceOfType($aroundMethodInterceptors, AroundMethodInterceptor::class);
    }

    public function execute(Message $message): ?MessageBuilder
    {
        $calledAggregate = $message->getHeaders()->containsKey(AggregateMessage::CALLED_AGGREGATE_OBJECT) ? $message->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_OBJECT) : null;

        $methodInvoker = new MethodInvoker(
            objectToInvokeOn: $calledAggregate ?: $this->aggregateClass,
            objectMethodName: $this->objectMethodName,
            methodParameterConverters: $this->methodParameterConverters,
            methodParameterNames: $this->methodParameterNames,
            canInterceptorReplaceArguments: false
        );

        if ($this->aroundMethodInterceptors) {
            $methodInvokerChainProcessor = new AroundMethodInvocation($message, $this->aroundMethodInterceptors, $methodInvoker);
            $result = $methodInvokerChainProcessor->proceed();
        } else {
            $result = $methodInvoker->executeEndpoint($message);
        }

        return $this->buildMessageFromResult($message, $result);
    }

    private function buildMessageFromResult(Message $message, mixed $result): ?MessageBuilder
    {
        $resultMessage = MessageBuilder::fromMessage($message);

        $resultType = TypeDescriptor::createFromVariable($result);
        if ($resultType->isIterable() && $this->returnType?->isCollection()) {
            $resultType = $this->returnType;
        }

        if (! is_null($result)) {
            if ($this->isCommandHandler) {
                $calledAggregate = $message->getHeaders()->containsKey(AggregateMessage::CALLED_AGGREGATE_OBJECT) ? $message->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_OBJECT) : null;
                $resultMessage = $resultMessage->setHeader(AggregateMessage::CALLED_AGGREGATE_OBJECT, $calledAggregate);
            }

            $resultMessage = $resultMessage
                ->setContentType(MediaType::createApplicationXPHPWithTypeParameter($resultType->toString()))
                ->setPayload($result)
            ;
        }

        if ($this->isCommandHandler && is_null($result)) {
            $resultMessage = $resultMessage->setHeader(AggregateMessage::NULL_EXECUTION_RESULT, true);
        }

        if ($this->isCommandHandler || ! is_null($result)) {
            return $resultMessage;
        }
        return null;
    }
}
