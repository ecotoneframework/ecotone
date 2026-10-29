<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Projecting;

class ProjectionV2StateHandler
{
    public function __construct(private ProjectionRegistry $projectionRegistry)
    {
    }

    public function getProjectionState(
        string $projectionName,
        ?string $aggregateId = null,
        ?string $streamName = null,
        ?string $aggregateType = null,
    ): mixed {
        $partitionKey = null;
        if ($aggregateId !== null && $streamName !== null && $aggregateType !== null) {
            $partitionKey = AggregatePartitionKey::compose($streamName, $aggregateType, $aggregateId);
        }

        $state = $this->projectionRegistry->get($projectionName)->loadState($partitionKey);

        return $state?->userState ?? [];
    }
}
