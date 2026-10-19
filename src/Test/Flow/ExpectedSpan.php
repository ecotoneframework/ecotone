<?php

declare(strict_types=1);

namespace Ecotone\Test\Flow;

use function array_map;

use Ecotone\Api\Tracing\Span;
use Ecotone\Api\Tracing\SpanKind;
use Ecotone\Api\Tracing\SpanStatus;

use function implode;
use function str_repeat;
use function str_starts_with;
use function var_export;

/**
 * licence Apache-2.0
 */
final class ExpectedSpan
{
    private const FRAMEWORK_NOISE_PREFIX = 'process MessageHeadersPropagatorInterceptor::';

    /**
     * @param array<string, scalar> $attributes
     * @param list<ExpectedSpan> $children
     * @param list<string> $absentAttributes
     * @param array<string, scalar> $attributesOtherThan
     * @param array<string, string> $attributesOfParent
     * @param list<array{string, array<string, scalar>}> $events
     */
    private function __construct(
        public readonly string $name,
        public readonly ?SpanKind $kind = null,
        public readonly ?SpanStatus $status = null,
        public readonly array $attributes = [],
        public readonly array $children = [],
        public readonly bool $root = false,
        public readonly array $absentAttributes = [],
        public readonly array $attributesOtherThan = [],
        public readonly array $attributesOfParent = [],
        public readonly array $events = [],
        public readonly ?string $traceId = null,
        public readonly ?string $parentSpanId = null,
        public readonly ?string $statusDescription = null,
    ) {
    }

    public function withoutAttributes(string ...$keys): self
    {
        return new self(...[...get_object_vars($this), 'absentAttributes' => [...$this->absentAttributes, ...$keys]]);
    }

    public function withAttributeOtherThan(string $key, int|float|string|bool $value): self
    {
        return new self(...[...get_object_vars($this), 'attributesOtherThan' => [...$this->attributesOtherThan, $key => $value]]);
    }

    public function withAttributeEqualToParents(string $key, ?string $parentKey = null): self
    {
        return new self(...[...get_object_vars($this), 'attributesOfParent' => [...$this->attributesOfParent, $key => $parentKey ?? $key]]);
    }

    /**
     * @param array<string, scalar> $attributes
     */
    public function withEventStartingWith(string $namePrefix, array $attributes = []): self
    {
        return new self(...[...get_object_vars($this), 'events' => [...$this->events, [$namePrefix, $attributes]]]);
    }

    public function inTrace(string $traceId): self
    {
        return new self(...[...get_object_vars($this), 'traceId' => $traceId]);
    }

    public function withParentSpanId(string $parentSpanId): self
    {
        return new self(...[...get_object_vars($this), 'parentSpanId' => $parentSpanId]);
    }

    public static function named(string $name): self
    {
        return new self($name);
    }

    public function asRoot(): self
    {
        return new self(...[...get_object_vars($this), 'root' => true]);
    }

    public function ofKind(SpanKind $kind): self
    {
        return new self(...[...get_object_vars($this), 'kind' => $kind]);
    }

    public function withStatus(SpanStatus $status, ?string $description = null): self
    {
        return new self(...[...get_object_vars($this), 'status' => $status, 'statusDescription' => $description]);
    }

    /**
     * @param array<string, scalar> $attributes
     */
    public function withAttributes(array $attributes): self
    {
        return new self(...[...get_object_vars($this), 'attributes' => [...$this->attributes, ...$attributes]]);
    }

    public function containing(ExpectedSpan ...$children): self
    {
        return new self(...[...get_object_vars($this), 'children' => [...$this->children, ...$children]]);
    }

    public function failsTheFlow(): bool
    {
        return $this->status === SpanStatus::Error || $this->hasFailedConsumer();
    }

    private function hasFailedConsumer(): bool
    {
        foreach ($this->children as $child) {
            if ((str_starts_with($child->name, 'receive ') && $child->status === SpanStatus::Error) || $child->hasFailedConsumer()) {
                return true;
            }
        }

        return false;
    }

    public function asStoredByFlowTracking(): ?self
    {
        if ($this->status === SpanStatus::Error) {
            return null;
        }

        return new self(...[...get_object_vars($this), 'children' => array_values(array_filter(array_map(fn (ExpectedSpan $child) => $child->asStoredByFlowTracking(), $this->children)))]);
    }

    public function count(): int
    {
        return 1 + array_sum(array_map(fn (ExpectedSpan $child) => $child->count(), $this->children));
    }

    /**
     * @param list<ExpectedSpan> $expected
     * @param Span[] $spans
     */
    public static function mismatchIn(array $expected, array $spans): ?string
    {
        $tree = SpanTree::of($spans);
        foreach ($expected as $expectedSpan) {
            if (! $expectedSpan->matchesAnyOf($tree->all(), $tree, null)) {
                return "No recorded span tree matches:\n" . $expectedSpan->render(1) . "\nRecorded:\n" . $tree->render();
            }
        }

        return null;
    }

    /**
     * @param Span[] $spans
     */
    public static function namedIn(string $name, array $spans): bool
    {
        foreach ($spans as $span) {
            if ($span->name === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Span[] $candidates
     */
    private function matchesAnyOf(array $candidates, SpanTree $tree, ?Span $parent): bool
    {
        foreach ($candidates as $candidate) {
            if ($this->matches($candidate, $tree, $parent ?? $tree->parentOf($candidate))) {
                return true;
            }
        }

        return false;
    }

    private function matches(Span $span, SpanTree $tree, ?Span $parent): bool
    {
        if ($span->name !== $this->name
            || ($this->root && $span->parentSpanId !== null)
            || ($this->kind !== null && $span->kind !== $this->kind)
            || ($this->status !== null && $span->status !== $this->status)) {
            return false;
        }
        foreach ($this->attributes as $key => $value) {
            if (($span->attributes[$key] ?? null) !== $value) {
                return false;
            }
        }
        foreach ($this->absentAttributes as $key) {
            if (array_key_exists($key, $span->attributes)) {
                return false;
            }
        }
        foreach ($this->attributesOtherThan as $key => $value) {
            if (! array_key_exists($key, $span->attributes) || $span->attributes[$key] === $value) {
                return false;
            }
        }
        foreach ($this->attributesOfParent as $key => $parentKey) {
            if ($parent === null || ! array_key_exists($key, $span->attributes) || $span->attributes[$key] !== ($parent->attributes[$parentKey] ?? null)) {
                return false;
            }
        }
        foreach ($this->events as [$namePrefix, $eventAttributes]) {
            if (! self::hasEvent($span, $namePrefix, $eventAttributes)) {
                return false;
            }
        }
        if (($this->traceId !== null && $span->traceId !== $this->traceId)
            || ($this->parentSpanId !== null && $span->parentSpanId !== $this->parentSpanId)
            || ($this->statusDescription !== null && $span->statusDescription !== $this->statusDescription)) {
            return false;
        }

        return self::childrenMatch($this->children, $tree->childrenOf($span), $tree, $span);
    }

    /**
     * @param array<string, scalar> $attributes
     */
    private static function hasEvent(Span $span, string $namePrefix, array $attributes): bool
    {
        foreach ($span->events as $event) {
            if (! str_starts_with($event->name, $namePrefix)) {
                continue;
            }
            foreach ($attributes as $key => $value) {
                if (($event->attributes[$key] ?? null) !== $value) {
                    continue 2;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * @param list<ExpectedSpan> $expectedChildren
     * @param Span[] $available
     */
    private static function childrenMatch(array $expectedChildren, array $available, SpanTree $tree, Span $parent): bool
    {
        if ($expectedChildren === []) {
            return true;
        }
        $expected = array_shift($expectedChildren);
        foreach ($available as $index => $candidate) {
            if ($expected->matches($candidate, $tree, $parent)) {
                $remaining = $available;
                unset($remaining[$index]);
                if (self::childrenMatch($expectedChildren, $remaining, $tree, $parent)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function render(int $depth): string
    {
        $line = str_repeat('  ', $depth) . $this->name
            . ($this->root ? ' <root>' : '')
            . ($this->absentAttributes === [] ? '' : ' without ' . implode(', ', $this->absentAttributes))
            . ($this->attributesOtherThan === [] ? '' : ' ' . implode(', ', array_map(fn (string $key, mixed $value) => $key . '!=' . var_export($value, true), array_keys($this->attributesOtherThan), $this->attributesOtherThan)))
            . ($this->attributesOfParent === [] ? '' : ' ' . implode(', ', array_map(fn (string $key, string $parentKey) => $key . '=parent.' . $parentKey, array_keys($this->attributesOfParent), $this->attributesOfParent)))
            . ($this->events === [] ? '' : ' events: ' . implode(', ', array_map(fn (array $event) => $event[0] . '…', $this->events)))
            . ($this->traceId === null ? '' : ' trace=' . $this->traceId)
            . ($this->parentSpanId === null ? '' : ' parent=' . $this->parentSpanId)
            . ($this->kind === null ? '' : ' [' . $this->kind->name . ']')
            . ($this->status === null ? '' : ' (' . $this->status->name . ')')
            . ($this->attributes === [] ? '' : ' ' . implode(', ', array_map(fn (string $key, mixed $value) => $key . '=' . var_export($value, true), array_keys($this->attributes), $this->attributes)));

        return implode("\n", [$line, ...array_map(fn (ExpectedSpan $child) => $child->render($depth + 1), $this->children)]);
    }

    public static function isFrameworkNoise(Span $span): bool
    {
        return str_starts_with($span->name, self::FRAMEWORK_NOISE_PREFIX);
    }
}
