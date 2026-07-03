<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler\ClosureInAttribute;

/**
 * licence Apache-2.0
 */
final class DelayCommand
{
    public function __construct(public readonly int $delay)
    {
    }
}
