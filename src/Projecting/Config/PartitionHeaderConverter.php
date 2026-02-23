<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Config;

use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;

class PartitionHeaderConverter implements ParameterConverter
{
    public function getArgumentFrom(Message $message): string
    {
        $streamName = $message->getHeaders()->get(MessageHeaders::EVENT_STREAM_NAME);
        $aggregateType = $message->getHeaders()->get(MessageHeaders::EVENT_AGGREGATE_TYPE);
        $aggregateId = $message->getHeaders()->get(MessageHeaders::EVENT_AGGREGATE_ID);

        return "{$streamName}:{$aggregateType}:{$aggregateId}";
    }
}
