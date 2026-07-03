<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute;

use Closure;

/**
 * Contract for attributes carrying an expression, either Expression Language string or closure (Enterprise).
 */
/**
 * licence Apache-2.0
 */
interface WithExpression
{
    public function getExpression(): string|Closure|null;
}
