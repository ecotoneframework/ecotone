<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\EventStoreAdapter;

use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\Config\Routing\BusRoutingMap;
use Ecotone\Modelling\Event;
use Ecotone\Projecting\ProjectorExecutor;

/**
 * Internal projector that forwards events from event store to a streaming channel.
 *
 * @internal
 */
class EventStoreChannelAdapterProjection implements ProjectorExecutor
{
    /**
     * @param array<string> $eventNames Glob patterns for filtering events (same as distributed bus)
     */
    public function __construct(
        private MessageChannel $outputChannel,
        private array $eventNames = []
    ) {
    }

    public function project(Event $event, mixed $userState = null): mixed
    {
        if (! empty($this->eventNames)) {
            $eventName = $event->getEventName();
            $matched = false;

            foreach ($this->eventNames as $pattern) {
                if (BusRoutingMap::globMatch($pattern, $eventName)) {
                    $matched = true;
                    break;
                }
            }

            if (! $matched) {
                return $userState;
            }
        }

        $payload = $event->getPayload();
        $metadata = $event->getMetadata();

        if (! isset($metadata[\Ecotone\Messaging\MessageHeaders::CONTENT_TYPE])) {
            $metadata[\Ecotone\Messaging\MessageHeaders::CONTENT_TYPE] = 'application/x-php';
        }

        $this->outputChannel->send(
            MessageBuilder::withPayload($payload)
                ->setMultipleHeaders($metadata)
                ->build()
        );

        return $userState;
    }

    public function init(): void
    {
        // No initialization needed
    }

    public function delete(): void
    {
        // No deletion needed
    }

    public function flush(): void
    {
        // No flushing needed
    }
}
