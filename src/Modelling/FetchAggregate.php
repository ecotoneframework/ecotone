<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * licence Apache-2.0
 */
class FetchAggregate implements MessageProcessor
{
    public function process(Message $message): ?Message
    {
        $aggregate = match (true) {
            $message->getHeaders()->containsKey(AggregateMessage::CALLED_AGGREGATE_OBJECT) => $message->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_OBJECT),
            $message->getHeaders()->containsKey(AggregateMessage::RESULT_AGGREGATE_OBJECT) => $message->getHeaders()->get(AggregateMessage::RESULT_AGGREGATE_OBJECT),
            default => null
        };
        if (! $aggregate) {
            return null;
        }
        return MessageBuilder::fromMessage($message)
            ->setPayload($aggregate)
            ->build();
    }
}
