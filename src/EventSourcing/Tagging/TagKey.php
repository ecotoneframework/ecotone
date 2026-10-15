<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing\Tagging;

/**
 * licence Apache-2.0
 */
final class TagKey
{
    public static function of(string $name, string $value): string
    {
        return $name . "\0" . $value;
    }
}
