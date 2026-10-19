<?php

declare(strict_types=1);

namespace Ecotone\Test\Flow;

use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Tracing\Span;
use Ecotone\Api\Tracing\SpanAttribute;
use Throwable;

/**
 * licence Apache-2.0
 */
final class FlowScenario
{
    /**
     * @param list<class-string> $classesToResolve
     * @param array<string|int, object> $services
     * @param list<object> $extensionObjects
     * @param list<FlowStage> $stages
     */
    public function __construct(
        public readonly string $name,
        public readonly array $classesToResolve,
        public readonly array $services,
        public readonly array $extensionObjects,
        public readonly array $stages,
    ) {
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function runStage(int $stageIndex, FlowTestSupport $ecotone, array $metadata = []): ?string
    {
        $stage = $this->stages[$stageIndex];
        try {
            ($stage->action)($ecotone, $metadata);
        } catch (Throwable $failure) {
            if ($stage->expectedFailure !== null && ($failure instanceof $stage->expectedFailure || str_contains($failure->getMessage(), $stage->expectedFailure))) {
                return null;
            }

            return "Stage {$stageIndex} failed unexpectedly: " . $failure::class . ': ' . $failure->getMessage();
        }

        return $stage->expectedFailure === null ? null : "Stage {$stageIndex} was expected to fail with \"{$stage->expectedFailure}\", but it completed";
    }

    public function isFinishedAfterStage(int $stageIndex): bool
    {
        $stage = $this->stages[$stageIndex];
        if ($stage->leavesWorkPending || $stage->expectedFailure !== null) {
            return false;
        }
        foreach ($stage->expectedSpans as $expectedSpan) {
            if ($expectedSpan->failsTheFlow()) {
                return false;
            }
        }

        return true;
    }

    public function asStoredByFlowTracking(): self
    {
        return new self($this->name, $this->classesToResolve, $this->services, $this->extensionObjects, array_map(fn (FlowStage $stage) => $stage->asStoredByFlowTracking(), $this->stages));
    }

    /**
     * @param Span[] $spans
     */
    public function mismatchAfterStage(int $stageIndex, array $spans): ?string
    {
        $stage = $this->stages[$stageIndex];
        $mismatch = ExpectedSpan::mismatchIn($stage->expectedSpans, $spans);
        if ($mismatch !== null) {
            return $mismatch;
        }
        $tree = SpanTree::of($spans);
        foreach ($tree->all() as $span) {
            if (($span->attributes[SpanAttribute::MESSAGING_SYSTEM] ?? null) !== SpanAttribute::ECOTONE) {
                return "Span {$span->name} does not name " . SpanAttribute::ECOTONE . ' as its ' . SpanAttribute::MESSAGING_SYSTEM . ". Recorded:\n" . $tree->render();
            }
        }
        foreach ($stage->absentSpanNames as $absentSpanName) {
            if (ExpectedSpan::namedIn($absentSpanName, $tree->all())) {
                return "Span {$absentSpanName} was recorded, though the stage expects it not to be. Recorded:\n" . $tree->render();
            }
        }
        $expectedCount = $this->expectedSpanCountUpTo($stageIndex);
        if ($stage->noOtherSpans && count($tree->all()) !== $expectedCount) {
            return 'Expected exactly ' . $expectedCount . ' spans, recorded ' . count($tree->all()) . ":\n" . $tree->render();
        }

        return null;
    }

    private function expectedSpanCountUpTo(int $stageIndex): int
    {
        $count = 0;
        foreach (array_slice($this->stages, 0, $stageIndex + 1) as $stage) {
            foreach ($stage->expectedSpans as $expectedSpan) {
                $count += $expectedSpan->count();
            }
        }

        return $count;
    }
}
