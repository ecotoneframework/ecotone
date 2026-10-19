<?php

declare(strict_types=1);

namespace Test\Ecotone\Tracing;

use Ecotone\Api\Tracing\InMemorySpanRecorder;
use Ecotone\Api\Tracing\Span;
use Ecotone\Api\Tracing\TracingConfiguration;

use function json_encode;

use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 */
abstract class TracingTestCase extends TestCase
{
    private const INTERNAL_NOISE_SPAN_NAME_PREFIX = 'process MessageHeadersPropagatorInterceptor::';

    protected static function recordingSpansInto(InMemorySpanRecorder $recorder): array
    {
        return [InMemorySpanRecorder::class => $recorder];
    }

    protected static function tracingConfiguration(): TracingConfiguration
    {
        return TracingConfiguration::createWithDefaults()->withSpanRecorder(InMemorySpanRecorder::class);
    }

    /**
     * @param Span[] $spans
     * @return string[]
     */
    public static function spanNamesWithoutFrameworkNoise(array $spans): array
    {
        return array_values(array_filter(
            array_map(fn (Span $span): string => $span->name, $spans),
            fn (string $name): bool => ! str_starts_with($name, self::INTERNAL_NOISE_SPAN_NAME_PREFIX),
        ));
    }

    /**
     * @param Span[] $spans
     */
    public static function buildTree(array $spans): array
    {
        $tree = [];
        foreach ($spans as $span) {
            if (str_starts_with($span->name, self::INTERNAL_NOISE_SPAN_NAME_PREFIX)) {
                continue;
            }

            $preparedSpan = [
                'details' => [
                    'name' => $span->name,
                    'trace_id' => $span->traceId,
                    'span_id' => $span->spanId,
                    'parent_id' => $span->parentSpanId,
                    'attributes' => $span->attributes,
                    'kind' => $span->kind,
                    'status' => $span->status,
                    'events' => $span->events,
                ],
                'children' => [],
            ];

            $tree = self::putInTree($preparedSpan, $tree, true);
        }

        return $tree;
    }

    public static function compareTreesByDetails(array $expectedTree, array $collectedTree): void
    {
        foreach ($expectedTree as $expectedNode) {
            self::findAndCompareNode($expectedNode, $collectedTree);
        }
    }

    private static function putInTree(array $searchedSpan, array $tree, bool $isRoot): array
    {
        $searchedSpanId = $searchedSpan['details']['span_id'];
        $searchedParentId = $searchedSpan['details']['parent_id'];

        foreach ($tree as $nodeId => $node) {
            if ($node['details']['parent_id'] === $searchedSpanId) {
                unset($tree[$nodeId]);
                $searchedSpan['children'][$nodeId] = $node;
            }
        }
        $changedStructure = $tree;

        foreach ($tree as $nodeId => $node) {
            if ($nodeId === $searchedParentId) {
                if (in_array($searchedSpanId, array_keys($node['children']))) {
                    continue;
                }

                $changedStructure[$nodeId]['children'][$searchedSpanId] = $searchedSpan;
            } elseif ($node['details']['parent_id'] === $searchedSpanId) {
                unset($changedStructure[$nodeId]);
                $searchedSpan['children'][$nodeId] = $node;

                $changedStructure = self::putInTree($searchedSpan, $changedStructure, true);
            } else {
                $changedStructure[$nodeId]['children'] = self::putInTree($searchedSpan, $node['children'], false);
            }
        }

        if ($isRoot && $changedStructure == $tree) {
            $changedStructure[$searchedSpanId] = $searchedSpan;
        }

        return $changedStructure;
    }

    private static function findAndCompareNode(array $expectedTreeNode, array $collectedTree): void
    {
        foreach ($collectedTree as $collectedNode) {
            if ($expectedTreeNode['details']['name'] !== $collectedNode['details']['name']) {
                continue;
            }
            if (isset($expectedTreeNode['details']['attributes']['flowId'])) {
                if (($collectedNode['details']['attributes']['flowId'] ?? null) != $expectedTreeNode['details']['attributes']['flowId']) {
                    continue;
                }
            }

            foreach ($expectedTreeNode['details'] as $expectedKey => $expectedValue) {
                self::assertArrayHasKey($expectedKey, $collectedNode['details']);
                if ($expectedKey === 'attributes') {
                    foreach ($expectedValue as $expectedAttributeKey => $expectedAttributeValue) {
                        self::assertArrayHasKey($expectedAttributeKey, $collectedNode['details']['attributes'], "Attribute {$expectedAttributeKey} missing on span {$collectedNode['details']['name']}");
                        self::assertSame($expectedAttributeValue, $collectedNode['details']['attributes'][$expectedAttributeKey]);
                    }
                } else {
                    self::assertSame($expectedValue, $collectedNode['details'][$expectedKey]);
                }
            }
            self::compareTreesByDetails($expectedTreeNode['children'], $collectedNode['children']);

            return;
        }

        self::fail("Could not find span named {$expectedTreeNode['details']['name']}. Spans at this level: " . json_encode(array_map(fn (array $node) => $node['details']['name'], array_values($collectedTree))));
    }

    public static function getNodeAtTargetedSpan(array $expectedTreeNode, array $collectedTree): array
    {
        foreach ($collectedTree as $collectedNode) {
            if ($expectedTreeNode['details']['name'] !== $collectedNode['details']['name']) {
                continue;
            }
            foreach ($expectedTreeNode['details'] as $expectedKey => $expectedValue) {
                if ($expectedKey === 'attributes') {
                    foreach ($expectedValue as $expectedAttributeKey => $expectedAttributeValue) {
                        self::assertSame($expectedAttributeValue, $collectedNode['details']['attributes'][$expectedAttributeKey] ?? null, "Attribute {$expectedAttributeKey} on span {$collectedNode['details']['name']}");
                    }
                } else {
                    self::assertSame($expectedValue, $collectedNode['details'][$expectedKey]);
                }
            }

            if (! array_key_exists('child', $expectedTreeNode)) {
                return $collectedNode;
            }

            return self::getNodeAtTargetedSpan($expectedTreeNode['child'], $collectedNode['children']);
        }

        self::fail("Could not find span named {$expectedTreeNode['details']['name']}. Spans at this level: " . json_encode(array_map(fn (array $node) => $node['details']['name'], array_values($collectedTree))));
    }
}
