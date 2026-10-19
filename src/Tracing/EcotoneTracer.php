<?php

declare(strict_types=1);

namespace Ecotone\Tracing;

use function array_search;
use function array_splice;
use function count;

use Ecotone\Api\Gateway\EcotoneClockInterface;
use Ecotone\Api\Tracing\Span;
use Ecotone\Api\Tracing\SpanContext;
use Ecotone\Api\Tracing\SpanKind;
use Ecotone\Api\Tracing\SpanRecorder;

/**
 * licence Apache-2.0
 */
final class EcotoneTracer implements Tracer
{
    /** @var EcotoneActiveSpan[] */
    private array $activeSpans = [];

    public function __construct(
        private EcotoneClockInterface $clock,
        private SpanRecorder $spanRecorder,
    ) {
    }

    public function startSpan(string $name, SpanKind $kind, array $attributes = [], ?SpanContext $remoteParent = null): ActiveSpan
    {
        $this->currentSpan()?->childStarted();

        return $this->start($name, $kind, $attributes, $remoteParent ?? $this->currentSpan()?->context());
    }

    public function startRootSpan(string $name, SpanKind $kind, array $attributes = []): ActiveSpan
    {
        return $this->start($name, $kind, $attributes, null);
    }

    /**
     * @param array<string, scalar> $attributes
     */
    private function start(string $name, SpanKind $kind, array $attributes, ?SpanContext $parent): ActiveSpan
    {
        $span = new EcotoneActiveSpan(
            $parent?->newChild() ?? SpanContext::newTrace(),
            $parent?->spanId,
            $name,
            $kind,
            $attributes,
            $this->clock->now(),
            $this->clock,
            $this,
        );
        $this->activeSpans[] = $span;

        return $span;
    }

    public function currentSpan(): ?ActiveSpan
    {
        return $this->activeSpans[count($this->activeSpans) - 1] ?? null;
    }

    public function endSpan(EcotoneActiveSpan $activeSpan, Span $span): void
    {
        $position = array_search($activeSpan, $this->activeSpans, true);
        if ($position !== false) {
            array_splice($this->activeSpans, $position, 1);
        }

        $this->spanRecorder->record($span);
    }
}
