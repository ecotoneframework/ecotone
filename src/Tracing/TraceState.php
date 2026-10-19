<?php

declare(strict_types=1);

namespace Ecotone\Tracing;

use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageHeaders;

/**
 * licence Apache-2.0
 */
final class TraceState
{
    private const VENDOR = 'ecotone';
    private const CARRIED = 'carried';
    private const UNTRACKED = 'untracked';

    public static function isCarried(Message $message): bool
    {
        return self::entryOfMessage($message) === self::CARRIED;
    }

    public static function isUntracked(Message $message): bool
    {
        return self::entryOfMessage($message) === self::UNTRACKED;
    }

    public static function withCarried(string $tracestate): string
    {
        return self::withEntry($tracestate, self::CARRIED);
    }

    public static function withUntracked(string $tracestate): string
    {
        return self::withEntry($tracestate, self::UNTRACKED);
    }

    private static function withEntry(string $tracestate, string $value): string
    {
        $entries = array_values(array_filter(array_map('trim', explode(',', $tracestate)), fn (string $entry): bool => $entry !== '' && ! str_starts_with($entry, self::VENDOR . '=')));

        return implode(',', [self::VENDOR . '=' . $value, ...$entries]);
    }

    private static function entryOfMessage(Message $message): ?string
    {
        $headers = $message->getHeaders();

        return $headers->containsKey(MessageHeaders::TRACESTATE) ? self::entryOf((string) $headers->get(MessageHeaders::TRACESTATE)) : null;
    }

    private static function entryOf(string $tracestate): ?string
    {
        foreach (explode(',', $tracestate) as $entry) {
            $entry = trim($entry);
            if (str_starts_with($entry, self::VENDOR . '=')) {
                return substr($entry, strlen(self::VENDOR) + 1);
            }
        }

        return null;
    }
}
