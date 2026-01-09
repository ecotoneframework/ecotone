<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ProjectionExecution
{
    public function __construct(
        /**
         * Configure the batch size for loading events during projection execution.
         * * This controls how many events are loaded from the stream in a single batch.
         *
         */
        public readonly int $eventLoadingBatchSize
    ) {
    }
}
