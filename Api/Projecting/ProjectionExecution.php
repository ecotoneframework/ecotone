<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Api\Projecting;

use Attribute;

/**
 * @link https://docs.ecotone.tech/modelling/event-sourcing/setting-up-projections/execution-modes#batch-size-and-flushing
 */
#[Attribute(Attribute::TARGET_CLASS)]
class ProjectionExecution
{
    public function __construct(
        public readonly int $eventLoadingBatchSize
    ) {
    }
}
