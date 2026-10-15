<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Api\Projecting;

use Attribute;
use Ecotone\Api\EventSourcing\EventStore;

/**
 * @link https://docs.ecotone.tech/modelling/event-sourcing/setting-up-projections/event-streams-and-handlers#from-a-named-stream
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
readonly class FromStream
{
    public function __construct(
        public string $stream,
        public ?string $aggregateType = null,
        public string $eventStoreReferenceName = EventStore::class
    ) {
    }
}
