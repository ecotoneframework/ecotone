<?php

declare(strict_types=1);

namespace Ecotone\Modelling;

use function array_map;
use function count;
use function json_encode;
use function reset;

/**
 * licence Apache-2.0
 */
final class AggregateIdString
{
    /**
     * @param array<string, mixed> $identifiers
     */
    public static function from(array $identifiers): string
    {
        if (count($identifiers) === 1) {
            return (string) reset($identifiers);
        }

        return json_encode(
            array_map(static fn (mixed $identifier): string => (string) $identifier, $identifiers),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }
}
