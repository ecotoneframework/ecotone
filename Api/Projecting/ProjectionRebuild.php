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
    ) {
        if ($this->partitionBatchSize < 1) {
            throw new InvalidArgumentException('Rebuild partition batch size must be at least 1');
        }
    }
}
