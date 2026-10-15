<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing\EventStore\Tag;

use Ecotone\Api\EventSourcing\AppendCondition;
use Ecotone\Api\EventSourcing\Event;
use Ecotone\Api\EventSourcing\EventCriteria;
use Ecotone\Api\EventSourcing\LoadedEvents;
use Ecotone\EventSourcing\EventStore\InMemoryEventStore;

/**
 * licence Apache-2.0
 */
interface InMemoryTagCollaborator
{
    public function loadByCriteria(InMemoryEventStore $eventStore, EventCriteria $criteria): LoadedEvents;

    /**
     * @param Event[] $events
     */
    public function appendEventsWithTagCondition(InMemoryEventStore $eventStore, string $streamName, array $events, ?AppendCondition $appendCondition): void;

    public function deleteTagIndexFor(string $streamName): void;

    public function bumpTagsGuarded(AppendCondition $appendCondition): void;
}
