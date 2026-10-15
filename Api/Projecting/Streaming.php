<?php

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
declare(strict_types=1);

namespace Ecotone\Api\Projecting;

use Attribute;

/**
 * @link https://docs.ecotone.tech/modelling/event-sourcing/setting-up-projections/scaling-and-advanced#streaming-projections
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Streaming
{
    public function __construct(
        public readonly string $channelName,
    ) {
    }
}
