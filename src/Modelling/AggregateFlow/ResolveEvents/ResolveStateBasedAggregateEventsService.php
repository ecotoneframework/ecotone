<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\ResolveEvents;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\ResolveAggregateEventsService;

final class ResolveStateBasedAggregateEventsService implements ResolveAggregateEventsService
{
    public function __construct(
        private bool $isFactoryMethod,
        private bool $resolveCalledAggregate,
        private ?string $aggregateMethodWithEvents,
    ) {
    }

    public function resolve(Message $message, array $metadata): Message
    {
        $resultMessage = MessageBuilder::fromMessage($message);

        if ($this->isFactoryMethod && $this->aggregateMethodWithEvents) {
            $events = call_user_func([$message->getPayload(), $this->aggregateMethodWithEvents]);
            $resultMessage->setHeader(AggregateMessage::RESULT_AGGREGATE_EVENTS, is_array($events) ? $events : []);
        } elseif ($this->resolveCalledAggregate && $this->aggregateMethodWithEvents) {
            $events = call_user_func([$message->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_OBJECT), $this->aggregateMethodWithEvents]);
            $resultMessage->setHeader(AggregateMessage::CALLED_AGGREGATE_EVENTS, is_array($events) ? $events : []);
        } elseif ($this->aggregateMethodWithEvents) {
            $events = call_user_func([$message->getPayload(), $this->aggregateMethodWithEvents]);
            $resultMessage->setHeader(AggregateMessage::RESULT_AGGREGATE_EVENTS, is_array($events) ? $events : []);
        }

        return $resultMessage->build();
    }
}
