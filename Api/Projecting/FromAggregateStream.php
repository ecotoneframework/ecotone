<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Api\Projecting;

use Attribute;
use Ecotone\Api\EventSourcing\EventStore;

/**
 * @link https://docs.ecotone.tech/modelling/event-sourcing/setting-up-projections/event-streams-and-handlers#from-aggregate-stream-recommended
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
readonly class FromAggregateStream
{
    /**
     * @param class-string $aggregateClass The aggregate class to read stream info from.
     *                                      Must be an EventSourcingAggregate.
     */
    public function __construct(
        public string $aggregateClass,
        public string $eventStoreReferenceName = EventStore::class
    ) {
    }
}
