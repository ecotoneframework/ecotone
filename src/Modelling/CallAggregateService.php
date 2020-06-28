<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\MessageHandlingException;
use Ecotone\Messaging\Handler\ParameterConverter;
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
     * @var array|ParameterConverterBuilder[]
     */
    private $parameterConverterBuilders;
    /**
     * @var ChannelResolver
     */
    private $channelResolver;
    /**
     * @var ReferenceSearchService
     */
    private $referenceSearchService;
    /**
     * @var array|AroundMethodInterceptor[]
     */
    private $aroundMethodInterceptors;
    /**
     * @var string|null
     */
    private $eventSourcedFactoryMethod;
    /**
     * @var bool
     */
    private $isCommandHandler;
    private string $aggregateClassName;
    private string $aggregateMethodName;
    private bool $isEventSourced;

    public function __construct(string $aggregateClassName, string $methodName, bool $isEventSourced, ChannelResolver $channelResolver, array $parameterConverterBuilders, array $aroundMethodInterceptors, ReferenceSearchService $referenceSearchService, ?string $eventSourcedFactoryMethod, bool $isCommand)
    {
        Assert::allInstanceOfType($parameterConverterBuilders, ParameterConverterBuilder::class);
        Assert::allInstanceOfType($aroundMethodInterceptors, AroundInterceptorReference::class);

        $this->parameterConverterBuilders = $parameterConverterBuilders;
        $this->channelResolver = $channelResolver;
        $this->referenceSearchService = $referenceSearchService;
        $this->aroundMethodInterceptors = $aroundMethodInterceptors;
        $this->eventSourcedFactoryMethod = $eventSourcedFactoryMethod;
        $this->isCommandHandler = $isCommand;
        $this->aggregateClassName = $aggregateClassName;
        $this->aggregateMethodName = $methodName;
        $this->isEventSourced = $isEventSourced;
    }

    public function call(Message $message) : ?Message
    {
        $aggregate = $message->getHeaders()->containsKey(AggregateMessage::AGGREGATE_OBJECT)
                            ? $message->getHeaders()->get(AggregateMessage::AGGREGATE_OBJECT)
                            : null;


        $methodInvoker = MethodInvoker::createWith(
            $aggregate ? $aggregate : $this->aggregateClassName,
            $this->aggregateMethodName,
            $this->parameterConverterBuilders,
            $this->referenceSearchService,
            $this->channelResolver,
            $this->aroundMethodInterceptors,
            []
        );

        $resultMessage = MessageBuilder::fromMessage($message);
        $result = $methodInvoker->processMessage($message);

        if (!$aggregate) {
            if ($this->isEventSourced) {
                $resultMessage = $resultMessage
                    ->setHeader(AggregateMessage::AGGREGATE_OBJECT, call_user_func([$this->aggregateClassName, $this->eventSourcedFactoryMethod], $result));
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