<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting;

use Ecotone\Messaging\Endpoint\Interceptor\TerminationListener;

class PartitionBatchExecutorHandler
{
    public const PARTITION_BATCH_EXECUTOR_CHANNEL = 'ecotone.projection.partition_batch.executor';

    public function __construct(
        private ProjectionRegistry $projectionRegistry,
        private TerminationListener $terminationListener,
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
    ): void {
        $projectingManager = $this->projectionRegistry->get($projectionName);
        $streamFilter = new StreamFilter($streamName, $aggregateType, $eventStoreReferenceName);

        foreach ($projectingManager->getPartitionProvider()->partitions($streamFilter, $limit, $offset) as $partition) {
            if ($shouldReset) {
                $projectingManager->executeWithReset($partition);
            } else {
                $projectingManager->execute($partition, true);
            }
            if ($this->terminationListener->shouldTerminate()) {
                break;
            }
        }
    }
}
