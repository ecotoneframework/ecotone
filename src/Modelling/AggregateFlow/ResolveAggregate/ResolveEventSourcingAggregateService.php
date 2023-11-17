<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\ResolveAggregate;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\EventSourcingHandlerExecutor;
use Ecotone\Modelling\ResolveAggregateService;

final class ResolveEventSourcingAggregateService implements ResolveAggregateService
{
    public function __construct(
        private bool $isFactoryMethod,
        private EventSourcingHandlerExecutor $eventSourcingHandlerExecutor,
    ) {
    }

    public function resolve(Message $message, array $metadata): Message
    {
        $resultMessage = MessageBuilder::fromMessage($message);

        if ($this->isFactoryMethod) {
            $events = $message->getHeaders()->get(AggregateMessage::RESULT_AGGREGATE_EVENTS);
            $resultMessage->setHeader(AggregateMessage::RESULT_AGGREGATE_OBJECT, $this->eventSourcingHandlerExecutor->fill($events, null));
        } else {
            $events = $message->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_EVENTS);
            $calledAggregate = $message->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_OBJECT);
            $resultMessage->setHeader(AggregateMessage::RESULT_AGGREGATE_OBJECT, $this->eventSourcingHandlerExecutor->fill($events, $calledAggregate));
        }

        return $resultMessage->build();
    }
}
