<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use function array_is_list;
use function array_keys;
use function count;
use function get_debug_type;
use function implode;
use function is_array;
use function is_bool;
use function is_scalar;
use function is_string;
use function mb_strlen;
use function mb_substr;
use function sprintf;

/**
 * licence Apache-2.0
 */
final class ExpressionResult
{
    private const MAXIMUM_DESCRIBED_STRING_LENGTH = 60;

    public static function describe(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'bool true' : 'bool false';
        }

        if (is_string($value)) {
            return sprintf("string '%s'", self::shortened($value));
        }

        if (is_array($value)) {
            return array_is_list($value)
                ? sprintf('a list of %d values', count($value))
                : sprintf("an array with keys '%s'", implode("', '", array_keys($value)));
        }

        return is_scalar($value) ? get_debug_type($value) . ' ' . $value : get_debug_type($value);
    }

    private static function shortened(string $value): string
    {
        return mb_strlen($value) > self::MAXIMUM_DESCRIBED_STRING_LENGTH
            ? mb_substr($value, 0, self::MAXIMUM_DESCRIBED_STRING_LENGTH) . '...'
            : $value;
    }
}
