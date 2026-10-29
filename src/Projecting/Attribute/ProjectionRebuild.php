<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Attribute;

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
