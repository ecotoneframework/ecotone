<?php

declare(strict_types=1);

namespace Ecotone\Api\Attribute;

use Attribute;
use Closure;
use Ecotone\Api\Scheduling\TimeSpan;
use Ecotone\Messaging\Attribute\WithExpression;

#[Attribute(Attribute::TARGET_METHOD)]
/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
final class Collected implements WithExpression
{
    public function __construct(
        public readonly ?int $maxSize = null,
        public readonly ?TimeSpan $maxWait = null,
        public readonly string|Closure|null $groupBy = null,
    ) {
    }

    public function getExpression(): string|Closure|null
    {
        return $this->groupBy;
    }
}
