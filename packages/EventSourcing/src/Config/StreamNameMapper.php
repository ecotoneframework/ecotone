<?php

namespace Ecotone\EventSourcing\Config;

use Ecotone\EventSourcing\Config\InboundChannelAdapter\ProjectionExecutor;
use Ecotone\EventSourcing\LazyProophProjectionManager;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;

final class StreamNameMapper
{
    public function map(Message $message): Message
    {
        return MessageBuilder::fromMessage($message)
                ->setHeader('ecotone.eventSourcing.eventStore.streamName', LazyProophProjectionManager::getProjectionStreamName(
                    $message->getHeaders()->get(ProjectionExecutor::PROJECTION_NAME)
                ))->build();
    }
}
