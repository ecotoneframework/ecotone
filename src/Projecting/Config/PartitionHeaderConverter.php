<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Config;

use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Projecting\AggregatePartitionKey;

class PartitionHeaderConverter implements ParameterConverter
{
    public function getArgumentFrom(Message $message): string
    {
        return AggregatePartitionKey::compose(
            $message->getHeaders()->get(MessageHeaders::EVENT_STREAM_NAME),
            $message->getHeaders()->get(MessageHeaders::EVENT_AGGREGATE_TYPE),
            $message->getHeaders()->get(MessageHeaders::EVENT_AGGREGATE_ID),
        );
    }
}
