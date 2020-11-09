<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundMethodInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class CallAggregateService
 * @package Ecotone\Modelling
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CallAggregateService
{
    /**
     * @var ParameterConverterBuilder[]
     */
    private array $parameterConverterBuilders;
    private ChannelResolver $channelResolver;
    private ReferenceSearchService $referenceSearchService;
    /**
     * @var AroundMethodInterceptor[]
     */
    private array $aroundMethodInterceptors;
    private bool $isCommandHandler;
    private bool $isEventSourced;
    private ?string $eventSourcedFactoryMethod;
    private bool $isFactoryMethod;
    private InterfaceToCall $aggregateInterface;

    public function __construct(InterfaceToCall $interfaceToCall, bool $isEventSourced, ChannelResolver $channelResolver, array $parameterConverterBuilders, array $aroundMethodInterceptors, ReferenceSearchService $referenceSearchService, bool $isCommand, bool $isFactoryMethod, ?string $eventSourcedFactoryMethod)
    {
        Assert::allInstanceOfType($parameterConverterBuilders, ParameterConverterBuilder::class);
        Assert::allInstanceOfType($aroundMethodInterceptors, AroundInterceptorReference::class);

        $this->parameterConverterBuilders = $parameterConverterBuilders;
        $this->channelResolver            = $channelResolver;
        $this->referenceSearchService     = $referenceSearchService;
        $this->aroundMethodInterceptors   = $aroundMethodInterceptors;
        $this->isCommandHandler           = $isCommand;
        $this->isEventSourced             = $isEventSourced;
        $this->eventSourcedFactoryMethod = $eventSourcedFactoryMethod;
        $this->isFactoryMethod = $isFactoryMethod;
        $this->aggregateInterface = $interfaceToCall;
    }

    public function call(Message $message): ?Message
    {
        $aggregate = $message->getHeaders()->containsKey(AggregateMessage::AGGREGATE_OBJECT)
            ? $message->getHeaders()->get(AggregateMessage::AGGREGATE_OBJECT)
            : null;

        $methodInvoker = MethodInvoker::createWith(
            $this->aggregateInterface,
            $aggregate ? $aggregate : $this->aggregateInterface->getInterfaceType()->toString(),
            $this->parameterConverterBuilders,
            $this->referenceSearchService,
            $this->channelResolver,
            $this->aroundMethodInterceptors,
            []
        );

        $resultMessage = MessageBuilder::fromMessage($message);
        $result        = $methodInvoker->processMessage($message);

        if ($this->isFactoryMethod) {
            if ($this->isEventSourced) {
                $resultMessage = $resultMessage
                    ->setHeader(
                        AggregateMessage::AGGREGATE_OBJECT,
                        call_user_func([$this->aggregateInterface->getInterfaceType()->toString(), $this->eventSourcedFactoryMethod], $result)
                    );
            }else {
                $resultMessage = $resultMessage
                    ->setHeader(AggregateMessage::AGGREGATE_OBJECT, $result);
            }
        }

        if (!is_null($result)) {
            $resultMessage = $resultMessage
                ->setPayload($result);
        }

        if ($this->isCommandHandler || !is_null($result)) {
            return $resultMessage
                ->build();
        }

        return null;
    }
}