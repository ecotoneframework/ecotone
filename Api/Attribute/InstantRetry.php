<?php

declare(strict_types=1);

namespace Ecotone\Api\Attribute;

use Attribute;

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 *
 * @link https://docs.ecotone.tech/modelling/recovering-tracing-and-monitoring/resiliency/retries#instant-retries-exceptions
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class InstantRetry
{
    public function __construct(
        public readonly int $retryTimes = 3,
        public readonly array $exceptions = []
    ) {
    }
}
