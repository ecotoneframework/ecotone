<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler\ClosureInAttribute;

use Attribute;
use Closure;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
/**
 * licence Apache-2.0
 */
final class Locking
{
    public function __construct(public readonly Closure $resource)
    {
    }
}
