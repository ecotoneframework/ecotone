<?php

declare(strict_types=1);

namespace Ecotone\Tracing;

use Ecotone\Api\Tracing\Span;
use Ecotone\Api\Tracing\SpanRecorder;

/**
 * licence Apache-2.0
 */
final class CompositeSpanRecorder implements SpanRecorder
{
    /**
     * @param SpanRecorder[] $spanRecorders
     */
    public function __construct(private array $spanRecorders)
    {
    }

    public function record(Span $span): void
    {
        foreach ($this->spanRecorders as $spanRecorder) {
            $spanRecorder->record($span);
        }
    }
}
