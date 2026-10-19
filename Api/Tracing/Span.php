<?php

declare(strict_types=1);

namespace Ecotone\Api\Tracing;

use Ecotone\Api\Scheduling\DatePoint;

/**
 * licence Apache-2.0
 */
final class Span
{
    /**
     * @param array<string, scalar> $attributes
     * @param SpanEvent[] $events
     */
    public function __construct(
        public readonly string $traceId,
        public readonly string $spanId,
        public readonly ?string $parentSpanId,
        public readonly string $name,
        public readonly SpanKind $kind,
        public readonly array $attributes,
        public readonly SpanStatus $status,
        public readonly ?string $statusDescription,
        public readonly array $events,
        public readonly DatePoint $startedAt,
        public readonly DatePoint $endedAt,
    ) {
    }

    public function isRoot(): bool
    {
        return $this->parentSpanId === null;
    }
}
