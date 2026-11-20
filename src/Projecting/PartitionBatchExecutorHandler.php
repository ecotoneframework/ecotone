<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Projecting;

class PartitionBatchExecutorHandler
{
    public const PARTITION_BATCH_EXECUTOR_CHANNEL = 'ecotone.projection.partition_batch.executor';

    public function __construct(
        private ProjectionRegistry $projectionRegistry,
    ) {
    }

    public function executeBatch(
        string $projectionName,
        ?int $limit = null,
        int $offset = 0,
        string $streamName = '',
        ?string $aggregateType = null,
        string $eventStoreReferenceName = '',
        bool $shouldReset = false,
        ?string $tag = null,
    ): void {
        $projectingManager = $this->projectionRegistry->get($projectionName);
        $streamFilter = new StreamFilter($streamName, $aggregateType, $eventStoreReferenceName, tag: $tag);

        foreach ($projectingManager->getPartitionProvider()->partitions($streamFilter, $limit, $offset) as $partition) {
            if ($shouldReset) {
                $projectingManager->executeWithReset($partition);
            } else {
                $projectingManager->executeUntilCaughtUp($partition);
            }
        }
    }
}
