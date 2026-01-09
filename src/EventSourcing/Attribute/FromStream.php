<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\EventSourcing\Attribute;

use Attribute;
use Ecotone\EventSourcing\EventStore;

/**
 * Configures a projection to read from a specific event stream.
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
