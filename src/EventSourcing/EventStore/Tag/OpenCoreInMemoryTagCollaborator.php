<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing\EventStore\Tag;

use Ecotone\Api\EventSourcing\AppendCondition;
use Ecotone\Api\EventSourcing\EventCriteria;
use Ecotone\Api\EventSourcing\LoadedEvents;
use Ecotone\EventSourcing\EventStore\InMemoryEventStore;
use Ecotone\EventSourcing\Tagging\DynamicConsistencyBoundaryDisabled;

/**
 * licence Apache-2.0
 */
final class OpenCoreInMemoryTagCollaborator implements InMemoryTagCollaborator
{
    public function loadByCriteria(InMemoryEventStore $eventStore, EventCriteria $criteria): LoadedEvents
    {
        throw DynamicConsistencyBoundaryDisabled::exception();
    }

    public function appendEventsWithTagCondition(InMemoryEventStore $eventStore, string $streamName, array $events, ?AppendCondition $appendCondition): void
    {
        throw DynamicConsistencyBoundaryDisabled::exception();
    }

    public function deleteTagIndexFor(string $streamName): void
    {
    }

    public function bumpTagsGuarded(AppendCondition $appendCondition): void
    {
        throw DynamicConsistencyBoundaryDisabled::exception();
    }
}
