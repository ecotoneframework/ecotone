<?php

declare(strict_types=1);

namespace Ecotone\Tracing;

use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * licence Apache-2.0
 */
final class TracingId
{
    private const TRACE_ID_PATTERN = '/^[0-9a-f]{32}$/';

    public static function normalize(string $tracingId): string
    {
        return self::normalizedOrNull($tracingId)
            ?? throw InvalidArgumentException::create(sprintf('tracingId `%s` is neither 32 hexadecimal characters nor a UUID. Pass a UUID such as Uuid::v7()->toRfc4122(), or a W3C trace id.', $tracingId));
    }

    public static function normalizedOrNull(string $tracingId): ?string
    {
        $candidate = strtolower(str_replace('-', '', $tracingId));
        if (preg_match(self::TRACE_ID_PATTERN, $candidate) !== 1 || $candidate === str_repeat('0', 32)) {
            return null;
        }

        return $candidate;
    }
}
