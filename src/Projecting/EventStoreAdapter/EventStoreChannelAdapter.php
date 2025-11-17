<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\EventStoreAdapter;

/**
 * Configuration for feeding events from event store into a streaming channel.
 * Creates a polling projection that continuously reads events and publishes them to the specified channel.
 *
 * Works with both PDO Event Store and In-Memory Event Store implementations.
 *
 * @example
 * #[ServiceContext]
 * public function eventStoreFeeder(): EventStoreChannelAdapter
 * {
 *     return EventStoreChannelAdapter::create(
 *         streamChannelName: 'event_stream',
 *         endpointId: 'event_store_feeder',
 *         fromStream: Ticket::class
 *     );
 * }
 *
 * licence Enterprise
 */
class EventStoreChannelAdapter
{
    private function __construct(
        public readonly string $streamChannelName,
        public readonly string $endpointId,
        public readonly string $fromStream,
        public readonly ?string $aggregateType = null,
        public readonly int $batchSize = 100,
        public readonly array $eventNames = [],
    ) {
    }

    public static function create(
        string $streamChannelName,
        string $endpointId,
        string $fromStream,
        ?string $aggregateType = null,
    ): self {
        return new self($streamChannelName, $endpointId, $fromStream, $aggregateType);
    }

    public function withBatchSize(int $batchSize): self
    {
        return new self(
            $this->streamChannelName,
            $this->endpointId,
            $this->fromStream,
            $this->aggregateType,
            $batchSize,
            $this->eventNames,
        );
    }

    /**
     * Filter events by name using glob patterns (same as distributed bus)
     * @param array<string> $eventNames Glob patterns like ['Ticket.*', 'Order.Created']
     */
    public function withEventNames(array $eventNames): self
    {
        return new self(
            $this->streamChannelName,
            $this->endpointId,
            $this->fromStream,
            $this->aggregateType,
            $this->batchSize,
            $eventNames,
        );
    }

    /**
     * @internal
     */
    public function getProjectionName(): string
    {
        return 'event_store_channel_adapter_' . $this->endpointId;
    }
}
