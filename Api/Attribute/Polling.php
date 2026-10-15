<?php

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
declare(strict_types=1);

namespace Ecotone\Api\Attribute;

use Attribute;

/**
 * @link https://docs.ecotone.tech/modelling/event-sourcing/setting-up-projections/execution-modes#polling-enterprise
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Polling
{
    public function __construct(
        public readonly string $endpointId,
    ) {
    }
}
