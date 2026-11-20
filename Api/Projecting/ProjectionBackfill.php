<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Api\Projecting;

use Attribute;
use InvalidArgumentException;

/**
 * @link https://docs.ecotone.tech/modelling/event-sourcing/setting-up-projections/backfill-and-rebuild
 */
#[Attribute(Attribute::TARGET_CLASS)]
class ProjectionBackfill
{
    public function __construct(
        public readonly int $backfillPartitionBatchSize = 100,
        public readonly ?string $asyncChannelName = null,
        public readonly ?string $batchCompletionChannelName = null,
    ) {
        if ($this->batchCompletionChannelName !== null && $this->asyncChannelName === null) {
            throw new InvalidArgumentException("#[ProjectionBackfill] names batchCompletionChannelName '{$this->batchCompletionChannelName}' but no asyncChannelName, so the backfill runs in the calling process and there is no asynchronous completion to consume. Add asyncChannelName to run the backfill asynchronously, or remove batchCompletionChannelName.");
        }
        if ($this->backfillPartitionBatchSize < 1) {
            throw new InvalidArgumentException('Backfill partition batch size must be at least 1');
        }
    }
}
