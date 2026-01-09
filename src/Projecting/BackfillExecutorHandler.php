<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting;

use Ecotone\Messaging\Endpoint\Interceptor\TerminationListener;

/**
 * Handles execution of projection backfill batches.
 * This handler is invoked via MessagingEntrypoint to execute backfill operations
 * for a given projection with specified limit and offset parameters.
 */
class BackfillExecutorHandler
{
    public const BACKFILL_EXECUTOR_CHANNEL = 'ecotone.projection.backfill.executor';

    public function __construct(
        private ProjectionRegistry $projectionRegistry,
        private TerminationListener $terminationListener,
    ) {
    }

    /**
     * Execute backfill for a specific partition batch.
     *
     * @param string $projectionName The name of the projection to backfill
     * @param int|null $limit The maximum number of partitions to process in this batch (null for unlimited)
     * @param int $offset The offset to start from
     * @param string $streamName The stream name to filter partitions
     * @param string|null $aggregateType The aggregate type to filter partitions (optional)
     * @param string $eventStoreReferenceName The event store reference name
     */
    public function executeBackfillBatch(
        string $projectionName,
        ?int $limit = null,
        int $offset = 0,
        string $streamName = '',
        ?string $aggregateType = null,
        string $eventStoreReferenceName = '',
    ): void {
        $projectingManager = $this->projectionRegistry->get($projectionName);
        $streamFilter = new StreamFilter($streamName, $aggregateType, $eventStoreReferenceName);

        foreach ($projectingManager->getPartitionProvider()->partitions($streamFilter, $limit, $offset) as $partition) {
            $projectingManager->execute($partition, true);
            if ($this->terminationListener->shouldTerminate()) {
                break;
            }
        }
    }
}
