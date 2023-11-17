<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\CallAggregate;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
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

final class CallEventSourcingAggregateService implements CallAggregateService
{
    private bool $isFactoryMethod;

    /**
     * @param array<ParameterConverter> $parameterConverters
     * @param array<AroundMethodInterceptor> $aroundMethodInterceptors
     */
    public function __construct(
        private InterfaceToCall $interfaceToCall,
        private array $parameterConverters,
        private array $aroundMethodInterceptors,
        private PropertyReaderAccessor $propertyReaderAccessor,
        private bool $isCommandHandler,
        private ?string $aggregateVersionProperty,
    ) {
        Assert::allInstanceOfType($parameterConverters, ParameterConverter::class);
        Assert::allInstanceOfType($aroundMethodInterceptors, AroundMethodInterceptor::class);

        $this->isFactoryMethod = $this->interfaceToCall->isFactoryMethod() ?? false;
    }

    public function call(Message $message): ?Message
    {
        $resultMessage = MessageBuilder::fromMessage($message);

        $calledAggregate = $message->getHeaders()->containsKey(AggregateMessage::CALLED_AGGREGATE_OBJECT) ? $message->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_OBJECT) : null;
        $versionBeforeHandling = $message->getHeaders()->containsKey(AggregateMessage::TARGET_VERSION) ? $message->getHeaders()->get(AggregateMessage::TARGET_VERSION) : null;

        if (is_null($versionBeforeHandling) && $this->aggregateVersionProperty) {
            if ($this->isFactoryMethod) {
                $versionBeforeHandling = 0;
            } else {
                $versionBeforeHandling = $this->propertyReaderAccessor->getPropertyValue(PropertyPath::createWith($this->aggregateVersionProperty), $calledAggregate);
                $versionBeforeHandling = is_null($versionBeforeHandling) ? 0 : $versionBeforeHandling;
            }

            $resultMessage = $resultMessage->setHeader(AggregateMessage::TARGET_VERSION, $versionBeforeHandling);
        }

        $methodInvoker = new MethodInvoker(
            objectToInvokeOn: $calledAggregate ?: $this->interfaceToCall->getInterfaceType()?->toString(),
            objectMethodName: $this->interfaceToCall->getMethodName(),
            methodParameterConverters: $this->parameterConverters,
            interfaceToCall: $this->interfaceToCall,
            canInterceptorReplaceArguments: false
        );

        if ($this->aroundMethodInterceptors) {
            $methodInvokerChainProcessor = new AroundMethodInvocation(requestMessage: $message, aroundMethodInterceptors: $this->aroundMethodInterceptors, interceptedMessageProcessor: $methodInvoker);
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

        if ($this->isCommandHandler || ! is_null($result)) {
            return $resultMessage->build();
        }

        return null;
    }
}
