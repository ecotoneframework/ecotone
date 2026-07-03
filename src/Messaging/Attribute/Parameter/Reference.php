<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute\Parameter;

use Attribute;
use Closure;
use Ecotone\Messaging\Attribute\WithExpression;

#[Attribute(Attribute::TARGET_PARAMETER)]
/**
 * licence Apache-2.0
 */
class Reference implements WithExpression
{
    public string $referenceName;

    private string|Closure|null $expression;

    public function __construct(
        string $referenceName = '',
        string|Closure|null $expression = null,
    ) {
        $this->referenceName = $referenceName;
        $this->expression    = $expression;
    }

    public function getReferenceName(): string
    {
        return $this->referenceName;
    }

    public function getExpression(): string|Closure|null
    {
        return $this->expression;
    }
}
