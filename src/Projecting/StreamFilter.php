<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting;

use Ecotone\EventSourcing\EventStore;

/**
 * Value object representing a filter for stream-based projections.
 * Contains stream name, optional aggregate type, event store reference, and event names.
 */
final class StreamFilter
{
    /**
     * @param array<string> $eventNames Event names to filter, empty array means no filtering
     */
    public function __construct(
        public readonly string $streamName,
        public readonly ?string $aggregateType = null,
        public readonly string $eventStoreReferenceName = EventStore::class,
        public readonly array $eventNames = [],
    ) {
    }
}
