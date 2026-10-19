<?php

declare(strict_types=1);

namespace Ecotone\Tracing;

use Ecotone\Api\Tracing\CurrentTrace;

/**
 * licence Apache-2.0
 */
final class TracerCurrentTrace implements CurrentTrace
{
    public function __construct(private Tracer $tracer)
    {
    }

    public function tracingId(): ?string
    {
        return $this->tracer->currentSpan()?->context()->traceId;
    }
}
