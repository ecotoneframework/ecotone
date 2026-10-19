<?php

declare(strict_types=1);

namespace Ecotone\Api\Tracing;

/**
 * licence Apache-2.0
 */
final class InMemorySpanRecorder implements SpanRecorder
{
    /** @var Span[] */
    private array $spans = [];

    public function record(Span $span): void
    {
        $this->spans[] = $span;
    }

    /**
     * @return Span[]
     */
    public function recordedSpans(): array
    {
        return $this->spans;
    }

    public function clear(): void
    {
        $this->spans = [];
    }
}
