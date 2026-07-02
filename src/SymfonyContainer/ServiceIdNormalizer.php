<?php

declare(strict_types=1);

namespace Ecotone\SymfonyContainer;

use function str_replace;

/**
 * licence Apache-2.0
 */
final class ServiceIdNormalizer
{
    public static function normalize(string $id): string
    {
        if (strpbrk($id, "\0\r\n'") === false) {
            return $id;
        }

        return str_replace(["\0", "\r", "\n", "'"], '.', $id);
    }
}
