<?php

declare(strict_types=1);

namespace Ecotone\Tracing;

use Ecotone\Api\Tracing\SpanContext;
use Ecotone\Api\Tracing\SpanKind;

/**
 * licence Apache-2.0
 */
interface Tracer
{
    /**
     * @param array<string, scalar> $attributes
     */
    public function startSpan(string $name, SpanKind $kind, array $attributes = [], ?SpanContext $remoteParent = null): ActiveSpan;

    /**
     * @param array<string, scalar> $attributes
     */
    public function startRootSpan(string $name, SpanKind $kind, array $attributes = []): ActiveSpan;

    public function currentSpan(): ?ActiveSpan;
}
