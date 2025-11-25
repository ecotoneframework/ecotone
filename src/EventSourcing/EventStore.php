<?php

namespace Ecotone\EventSourcing;

use Ecotone\EventSourcing\EventStore\MetadataMatcher;
use Ecotone\Modelling\Event;

/**
 * licence Apache-2.0
 */
interface EventStore
{
    /**
     * Creates new Stream with Metadata and appends events to it
     *
     * @param Event[]|object[]|array[] $streamEvents
     */
    public function create(string $streamName, array $streamEvents = [], array $streamMetadata = []): void;
    /**
     * Appends events to existing Stream, or creates one and then appends events if it does not exists
     *
     * @param Event[]|object[]|array[] $streamEvents
     */
    public function appendTo(string $streamName, array $streamEvents): void;

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
}
