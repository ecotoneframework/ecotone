<?php

declare(strict_types=1);

namespace Ecotone\Api\Attribute;

use Attribute;
use Closure;
use Ecotone\Messaging\Attribute\WithExpression;

#[Attribute(Attribute::TARGET_PARAMETER)]
/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
class Fetch implements WithExpression
{
    public string|Closure $expression;

    public function __construct(string|Closure $expression)
    {
        $this->expression = $expression;
    }

    public function getExpression(): string|Closure
    {
        return $this->expression;
    }
}
