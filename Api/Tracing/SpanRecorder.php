<?php

declare(strict_types=1);

namespace Ecotone\Api\Tracing;

/**
 * licence Apache-2.0
 */
interface SpanRecorder
{
    public function record(Span $span): void;
}
