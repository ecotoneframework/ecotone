<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\CallAggregate;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundMethodInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundMethodInvocation;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\CallAggregateService;

final class CallStateBasedAggregateService implements CallAggregateService
{
    /**
     * @param array<ParameterConverter> $parameterConverters
     * @param array<AroundMethodInterceptor> $aroundMethodInterceptors
     */
    public function __construct(
        private InterfaceToCall $interfaceToCall,
        private array $parameterConverters,
        private array $aroundMethodInterceptors,
        private bool $isCommandHandler,
    ) {
        Assert::allInstanceOfType($parameterConverters, ParameterConverter::class);
        Assert::allInstanceOfType($aroundMethodInterceptors, AroundMethodInterceptor::class);
    }

    public function call(Message $message): ?Message
    {
        $resultMessage = MessageBuilder::fromMessage($message);

        $calledAggregate = $message->getHeaders()->containsKey(AggregateMessage::CALLED_AGGREGATE_OBJECT) ? $message->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_OBJECT) : null;

        $methodInvoker = new MethodInvoker(
            objectToInvokeOn: $calledAggregate ?: $this->interfaceToCall->getInterfaceType()->toString(),
            objectMethodName: $this->interfaceToCall->getMethodName(),
            methodParameterConverters: $this->parameterConverters,
            interfaceToCall: $this->interfaceToCall,
            canInterceptorReplaceArguments: false
        );

        if ($this->aroundMethodInterceptors) {
            $methodInvokerChainProcessor = new AroundMethodInvocation($message, $this->aroundMethodInterceptors, $methodInvoker);
            $result = $methodInvokerChainProcessor->proceed();
        } else {
            $result = $methodInvoker->executeEndpoint($message);
        }

        $resultType = TypeDescriptor::createFromVariable($result);
        if ($resultType->isIterable() && $this->interfaceToCall->getReturnType()?->isCollection()) {
            $resultType = $this->interfaceToCall->getReturnType();
        }

        if (! is_null($result)) {
            if ($this->isCommandHandler) {
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
            return $resultMessage->build();
        }

        return null;
    }
}
