<?php

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
declare(strict_types=1);

namespace Ecotone\Api\Projecting;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Partitioned
{
    public function __construct(
        public readonly ?string $partitionHeaderName = null,
    ) {
    }
}
