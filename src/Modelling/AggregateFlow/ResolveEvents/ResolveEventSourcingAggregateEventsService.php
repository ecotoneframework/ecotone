<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\ResolveEvents;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\ResolveAggregateEventsService;

final class ResolveEventSourcingAggregateEventsService implements ResolveAggregateEventsService
{
    public function __construct(
        private bool $isFactoryMethod,
        private ?string $aggregateMethodWithEvents,
    ) {
    }

    public function resolve(Message $message, array $metadata): Message
    {
        $resultMessage = MessageBuilder::fromMessage($message);

        if ($this->isFactoryMethod) {
            if ($message->getHeaders()->containsKey(AggregateMessage::RESULT_AGGREGATE_EVENTS)) {
                return $resultMessage->build();
            }
            if ($this->aggregateMethodWithEvents) {
                $resultAggregateEvents = call_user_func([$message->getPayload(), $this->aggregateMethodWithEvents]);
            } else {
                $resultAggregateEvents = $message->getPayload();
            }
            $resultMessage->setHeader(AggregateMessage::RESULT_AGGREGATE_EVENTS, is_array($resultAggregateEvents) ? $resultAggregateEvents : []);
        } else {
            if ($message->getHeaders()->containsKey(AggregateMessage::CALLED_AGGREGATE_EVENTS)) {
                return $resultMessage->build();
            }
            if ($this->aggregateMethodWithEvents) {
                $calledAggregateEvents = call_user_func([$message->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_OBJECT), $this->aggregateMethodWithEvents]);
            } else {
                $calledAggregateEvents = $message->getPayload();
            }
            $resultMessage->setHeader(AggregateMessage::CALLED_AGGREGATE_EVENTS, is_array($calledAggregateEvents) ? $calledAggregateEvents : []);
        }

        return $resultMessage->build();
    }
}
