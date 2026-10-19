<?php

declare(strict_types=1);

namespace Ecotone\Tracing;

use Ecotone\Api\Gateway\EcotoneClockInterface;
use Ecotone\Api\Scheduling\DatePoint;
use Ecotone\Api\Tracing\Span;
use Ecotone\Api\Tracing\SpanContext;
use Ecotone\Api\Tracing\SpanEvent;
use Ecotone\Api\Tracing\SpanKind;
use Ecotone\Api\Tracing\SpanStatus;
use Throwable;

/**
 * licence Apache-2.0
 */
final class EcotoneActiveSpan implements ActiveSpan
{
    public const UNIT_OF_WORK_FAILED = 'The unit of work this span wraps failed and its failure was handled by the error channel or the acknowledgement strategy';
    /** @var SpanEvent[] */
    private array $events = [];
    private bool $ended = false;
    private bool $unitOfWorkFailed = false;
    private bool $childrenStarted = false;

    /**
     * @param array<string, scalar> $attributes
     */
    public function __construct(
        private SpanContext $context,
        private ?string $parentSpanId,
        private string $name,
        private SpanKind $kind,
        private array $attributes,
        private DatePoint $startedAt,
        private EcotoneClockInterface $clock,
        private EcotoneTracer $tracer,
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function attributes(): array
    {
        return $this->attributes;
    }

    public function setAttribute(string $key, int|float|string|bool $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function childStarted(): void
    {
        $this->childrenStarted = true;
    }

    public function hasStartedChildren(): bool
    {
        return $this->childrenStarted;
    }

    public function context(): SpanContext
    {
        return $this->context;
    }

    public function addEvent(string $name, array $attributes = []): void
    {
        $this->events[] = new SpanEvent($name, $attributes, $this->clock->now());
    }

    public function end(): void
    {
        if ($this->unitOfWorkFailed) {
            $this->finish(SpanStatus::Error, self::UNIT_OF_WORK_FAILED);

            return;
        }
        $this->finish(SpanStatus::Ok, null);
    }

    public function endedSoFar(): Span
    {
        return new Span(
            $this->context->traceId,
            $this->context->spanId,
            $this->parentSpanId,
            $this->name,
            $this->kind,
            $this->attributes,
            SpanStatus::Ok,
            null,
            $this->events,
            $this->startedAt,
            $this->clock->now(),
        );
    }

    public function markUnitOfWorkFailed(): void
    {
        $this->unitOfWorkFailed = true;
    }

    public function markUnitOfWorkRecovered(): void
    {
        $this->unitOfWorkFailed = false;
    }

    public function fail(Throwable $failure): void
    {
        $this->addEvent('exception', [
            'exception.type' => $failure::class,
            'exception.message' => $failure->getMessage(),
            'exception.stacktrace' => $failure->getTraceAsString(),
        ]);
        $this->finish(SpanStatus::Error, $failure->getMessage());
    }

    private function finish(SpanStatus $status, ?string $statusDescription): void
    {
        if ($this->ended) {
            return;
        }
        $this->ended = true;

        $this->tracer->endSpan($this, new Span(
            $this->context->traceId,
            $this->context->spanId,
            $this->parentSpanId,
            $this->name,
            $this->kind,
            $this->attributes,
            $status,
            $statusDescription,
            $this->events,
            $this->startedAt,
            $this->clock->now(),
        ));
    }
}
