<?php

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
declare(strict_types=1);

namespace Ecotone\Api\Projecting;

use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_CLASS)]
class ProjectionRebuild
{
    public function __construct(
        public readonly int $partitionBatchSize = 100,
        public readonly ?string $asyncChannelName = null,
        public readonly ?string $batchCompletionChannelName = null,
    ) {
        if ($this->batchCompletionChannelName !== null && $this->asyncChannelName === null) {
            throw new InvalidArgumentException("#[ProjectionRebuild] names batchCompletionChannelName '{$this->batchCompletionChannelName}' but no asyncChannelName, so the rebuild runs in the calling process and there is no asynchronous completion to consume. Add asyncChannelName to run the rebuild asynchronously, or remove batchCompletionChannelName.");
        }
        if ($this->partitionBatchSize < 1) {
            throw new InvalidArgumentException('Rebuild partition batch size must be at least 1');
        }
    }
}
