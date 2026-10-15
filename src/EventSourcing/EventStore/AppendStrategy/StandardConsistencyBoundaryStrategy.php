<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing\EventStore\AppendStrategy;

use Ecotone\Api\EventSourcing\AppendCondition;
use Ecotone\EventSourcing\Tagging\DynamicConsistencyBoundaryDisabled;

/**
 * licence Apache-2.0
 */
final class StandardConsistencyBoundaryStrategy implements AppendStrategy
{
    public function append(AppendableStore $store, string $streamName, array $events, ?AppendCondition $appendCondition): void
    {
        if ($appendCondition !== null && $appendCondition->hasTagCondition()) {
            throw DynamicConsistencyBoundaryDisabled::exception();
        }

        if ($appendCondition !== null && $appendCondition->hasAggregateCondition()) {
            $store->appendEventsWithAggregateCondition($streamName, $events, $appendCondition);

            return;
        }

        $store->appendEventsUnconditionally($streamName, $events);
    }
}
