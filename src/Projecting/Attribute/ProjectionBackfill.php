<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Attribute;

use Attribute;
use InvalidArgumentException;

/**
 * Configure projection backfill settings.
 * This attribute controls how partitions are batched during backfill operations.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class ProjectionBackfill
{
    public function __construct(
        /**
         * Number of partitions to process in a single batch during backfill.
         * Must be at least 1.
         */
        public readonly int $backfillPartitionBatchSize = 100,
        /**
         * Async channel name for backfill operations.
         * When set, backfill batches are sent to this channel first, then routed to the backfill handler.
         * When null, backfill executes synchronously.
         */
        public readonly ?string $asyncChannelName = null,
    ) {
        if ($this->backfillPartitionBatchSize < 1) {
            throw new InvalidArgumentException('Backfill partition batch size must be at least 1');
        }
    }
}
