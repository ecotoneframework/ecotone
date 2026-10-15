<?php

declare(strict_types=1);

namespace Ecotone\Api\Attribute;

use Attribute;

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class DecisionBoundary
{
}
