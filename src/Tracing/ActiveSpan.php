<?php

declare(strict_types=1);

namespace Ecotone\Tracing;

use Ecotone\Api\Tracing\Span;
use Ecotone\Api\Tracing\SpanContext;
use Throwable;

/**
 * licence Apache-2.0
 */
interface ActiveSpan
{
    public function context(): SpanContext;

    public function name(): string;

    /**
     * @return array<string, scalar>
     */
    public function attributes(): array;

    /**
     * @param array<string, scalar|null> $attributes
     */
    public function addEvent(string $name, array $attributes = []): void;

    public function setAttribute(string $key, int|float|string|bool $value): void;

    public function childStarted(): void;

    public function hasStartedChildren(): bool;

    public function end(): void;

    public function endedSoFar(): Span;

    public function markUnitOfWorkFailed(): void;

    public function markUnitOfWorkRecovered(): void;

    public function fail(Throwable $failure): void;
}
