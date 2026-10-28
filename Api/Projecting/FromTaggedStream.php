<?php

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
declare(strict_types=1);

namespace Ecotone\Api\Projecting;

use Attribute;
use Ecotone\Api\EventSourcing\EventStore;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class FromTaggedStream
{
    public function __construct(
        public string $tag,
        public string $eventStoreReferenceName = EventStore::class,
    ) {
    }
}
