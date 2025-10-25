<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ProjectionBatchSize
{
    public function __construct(public readonly int $batchSize)
    {
    }
}
