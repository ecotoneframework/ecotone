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
        if ($message->getHeaders()->containsKey(AggregateMessage::CALLED_AGGREGATE_INSTANCE)) {
            return MessageBuilder::fromMessage($message)
                ->setPayload($message->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_INSTANCE))
                ->build();
        }

        return null;
    }
}
