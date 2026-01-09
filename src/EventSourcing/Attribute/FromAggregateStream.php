<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\EventSourcing\Attribute;

use Attribute;
use Ecotone\EventSourcing\EventStore;

/**
 * Configures a projection to read from an aggregate's event stream.
 * Automatically resolves Stream and AggregateType from the aggregate class.
 *
 * Example usage:
 * ```php
 * #[ProjectionV2('order_list')]
 * #[FromAggregateStream(Order::class)]
 * class OrderListProjection { ... }
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
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
