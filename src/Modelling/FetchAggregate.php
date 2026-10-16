<?php

namespace Ecotone\Modelling;

use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * licence Apache-2.0
 */
class FetchAggregate implements MessageProcessor
{
    public function __construct(private MessageBuilder $messageBuilder)
    {
    }

    public function process(Message $message): ?Message
    {
        if ($message->getHeaders()->containsKey(AggregateMessage::CALLED_AGGREGATE_INSTANCE)) {
            return $this->messageBuilder->fromMessage($message)
                ->setPayload($message->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_INSTANCE))
                ->build();
        }

        return null;
    }
}
