<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Attribute;

use Attribute;
use Ecotone\Messaging\MessageHeaders;

/*
 * Allows configuring a custom partition header name for partitioned projections.
 * For Aggregate scope, use MessageHeaders::EVENT_AGGREGATE_ID.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Partitioned
{
    public function __construct(
        public readonly string $partitionHeaderName = MessageHeaders::EVENT_AGGREGATE_ID,
    ) {
    }
}
