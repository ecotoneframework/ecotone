<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler\ClosureInAttribute;

/**
 * licence Apache-2.0
 */
final class UpperCaseService
{
    public function transform(string $value): string
    {
        return strtoupper($value);
    }
}
