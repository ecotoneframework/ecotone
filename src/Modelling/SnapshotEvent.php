<?php

namespace Ecotone\Modelling;

/**
 * @TODO Ecotone 2.0 - change EventSourcingRepository to fetch for set of events.
 * as right now storing snapshots is inside SaveAggregateBuilder and creating SnapshotEvent in Event Sourcing package
 */
/**
 * licence Apache-2.0
 */
final class SnapshotEvent
{
    public function __construct(private object $aggregate)
    {
    }

    public function getAggregate(): object
    {
        return $this->aggregate;
    }
}
