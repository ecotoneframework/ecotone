<?php

declare(strict_types=1);

namespace Ecotone\Api\EventSourcing;

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
final class LoadedEvents
{
    /**
     * @param Event[] $events
     */
    public function __construct(
        public readonly array $events,
        public readonly AppendCondition $appendCondition,
    ) {
    }
}
