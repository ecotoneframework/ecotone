<?php

declare(strict_types=1);

namespace Ecotone\Test\Flow;

use function array_filter;
use function array_values;

use Ecotone\Api\Tracing\Span;

use function implode;
use function str_repeat;

/**
 * licence Apache-2.0
 */
final class SpanTree
{
    /**
     * @param list<Span> $spans
     * @param array<string, list<Span>> $childrenByParent
     * @param array<string, Span> $spansById
     */
    private function __construct(
        private readonly array $spans,
        private readonly array $childrenByParent,
        private readonly array $spansById,
    ) {
    }

    public function parentOf(Span $span): ?Span
    {
        return $span->parentSpanId === null ? null : ($this->spansById[$span->parentSpanId] ?? null);
    }

    /**
     * @param Span[] $spans
     */
    public static function of(array $spans): self
    {
        $spans = array_values(array_filter($spans, fn (Span $span) => ! ExpectedSpan::isFrameworkNoise($span)));
        $childrenByParent = [];
        $spansById = [];
        foreach ($spans as $span) {
            $spansById[$span->spanId] = $span;
            if ($span->parentSpanId !== null) {
                $childrenByParent[$span->parentSpanId][] = $span;
            }
        }

        return new self($spans, $childrenByParent, $spansById);
    }

    /**
     * @return list<Span>
     */
    public function all(): array
    {
        return $this->spans;
    }

    /**
     * @return list<Span>
     */
    public function roots(): array
    {
        $ids = [];
        foreach ($this->spans as $span) {
            $ids[$span->spanId] = true;
        }

        return array_values(array_filter($this->spans, fn (Span $span) => $span->parentSpanId === null || ! isset($ids[$span->parentSpanId])));
    }

    /**
     * @return list<Span>
     */
    public function childrenOf(Span $span): array
    {
        return $this->childrenByParent[$span->spanId] ?? [];
    }

    public function render(): string
    {
        return implode("\n", array_map(fn (Span $root) => $this->renderSpan($root, 1), $this->roots()));
    }

    private function renderSpan(Span $span, int $depth): string
    {
        return implode("\n", [
            str_repeat('  ', $depth) . $span->name . ' [' . $span->kind->name . '] (' . $span->status->name . ')',
            ...array_map(fn (Span $child) => $this->renderSpan($child, $depth + 1), $this->childrenOf($span)),
        ]);
    }
}
