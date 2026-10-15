<?php

namespace Ecotone\Api\EventSourcing;

/**
 * licence Apache-2.0
 */
interface EventStore
{
    public const RAW_REFERENCE = 'ecotone.eventSourcing.eventStore.instance';

    /**
     * @param Event[]|object[]|array[] $streamEvents
     */
    public function appendTo(string $streamName, array $streamEvents, ?AppendCondition $appendCondition = null): void;

    public function delete(string $streamName): void;

    public function hasStream(string $streamName): bool;

    /**
     * @return Event[]
     */
    public function load(
        string $streamName,
        int $fromNumber = 1,
        ?int $count = null,
        ?MetadataMatcher $metadataMatcher = null,
        bool $deserialize = true
    ): iterable;

    /**
     * @param string[] $eventNames
     * @return Event[]
     */
    public function loadAggregateEvents(
        string $streamName,
        ?string $aggregateType,
        string $aggregateId,
        int $fromVersion = 1,
        ?int $count = null,
        array $eventNames = [],
        bool $deserialize = true
    ): iterable;

    public function loadByCriteria(EventCriteria $criteria): LoadedEvents;
}
