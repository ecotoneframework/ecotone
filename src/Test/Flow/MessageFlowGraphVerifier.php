<?php

declare(strict_types=1);

namespace Ecotone\Test\Flow;

use function array_filter;
use function array_shift;
use function class_exists;

use Ecotone\Api\Gateway\DistributedBus;
use Ecotone\Api\Messaging\Flow\MessageFlowGraph;
use Ecotone\Api\Tracing\Span;
use Ecotone\Api\Tracing\SpanAttribute;
use Ecotone\Modelling\Config\Routing\BusRoutingMap;

use function in_array;
use function is_a;
use function is_string;
use function str_starts_with;
use function substr;

/**
 * licence Apache-2.0
 */
final class MessageFlowGraphVerifier
{
    /**
     * @param Span[] $spans
     */
    public static function mismatchIn(MessageFlowGraph $graph, array $spans): ?string
    {
        $tree = SpanTree::of($spans);
        $steps = [];
        foreach ($tree->roots() as $root) {
            $steps = [...$steps, ...self::stepsFrom($root, null, $tree, $graph)];
        }

        $handlerRan = array_filter($tree->all(), fn (Span $span) => isset($span->attributes[SpanAttribute::CODE_FUNCTION_NAME]));
        if ($steps === [] && $handlerRan !== []) {
            return "Handlers ran, yet no recorded step could be placed in the message flow graph. Recorded:\n" . $tree->render();
        }
        foreach ($steps as [$from, $to]) {
            if (! self::hasPath($graph, $from, $to)) {
                return "The recorded step {$from} -> {$to} has no path in the message flow graph:\n" . $graph->toMermaid();
            }
        }

        return null;
    }

    /**
     * @return list<array{string, string}>
     */
    private static function stepsFrom(Span $span, ?string $nearestNode, SpanTree $tree, MessageFlowGraph $graph): array
    {
        $node = self::nodeOf($span, $graph);
        $isChannel = $node !== null && str_starts_with($node, 'channel:');
        $steps = [];
        if ($node !== null && ! $isChannel && $nearestNode !== null && $nearestNode !== $node) {
            $steps[] = [$nearestNode, $node];
        }
        foreach ($tree->childrenOf($span) as $child) {
            $steps = [...$steps, ...self::stepsFrom($child, $node ?? $nearestNode, $tree, $graph)];
        }
        if ($isChannel && $nearestNode !== null && ! str_starts_with($nearestNode, 'channel:')) {
            $reachedBelow = self::nearestNodesBelow($span, $tree, $graph);
            foreach ($reachedBelow === [] ? [$node] : $reachedBelow as $reached) {
                $steps[] = [$nearestNode, $reached];
            }
        }

        return $steps;
    }

    /**
     * @return list<string>
     */
    private static function nearestNodesBelow(Span $span, SpanTree $tree, MessageFlowGraph $graph): array
    {
        $nodes = [];
        foreach ($tree->childrenOf($span) as $child) {
            $node = self::nodeOf($child, $graph);
            $nodes = $node === null || str_starts_with($node, 'channel:')
                ? [...$nodes, ...self::nearestNodesBelow($child, $tree, $graph)]
                : [...$nodes, $node];
        }

        return $nodes;
    }

    private static function nodeOf(Span $span, MessageFlowGraph $graph): ?string
    {
        $destination = $span->attributes[SpanAttribute::MESSAGING_DESTINATION_NAME] ?? null;
        $node = match (true) {
            in_array($span->name, ['send command', 'send event', 'send query'], true) => self::messageNodeOf((string) $destination, $graph),
            $span->name === 'send distributed' => 'distributed:' . DistributedBus::class,
            isset($span->attributes[SpanAttribute::PROJECTION_NAME]) && (str_starts_with($span->name, 'process ') || isset($span->attributes[SpanAttribute::PROJECTION_OPERATION])) => 'projection:' . $span->attributes[SpanAttribute::PROJECTION_NAME],
            isset($span->attributes[SpanAttribute::CODE_FUNCTION_NAME]) => 'handler:' . $span->attributes[SpanAttribute::CODE_FUNCTION_NAME],
            str_starts_with($span->name, 'send ') => 'channel:' . substr($span->name, 5),
            str_starts_with($span->name, 'receive ') => 'channel:' . substr($span->name, 8),
            default => null,
        };

        return $node !== null && $graph->hasNode($node) ? $node : null;
    }

    private static function messageNodeOf(string $destination, MessageFlowGraph $graph): ?string
    {
        if ($graph->hasNode('message:' . $destination)) {
            return 'message:' . $destination;
        }
        foreach ($graph->nodes() as $node) {
            if (! $node->kind->isMessage()) {
                continue;
            }
            $subscription = (string) ($node->attributes['routingKey'] ?? '');
            if (($node->attributes['namedAs'] ?? null) === $destination
                || ($subscription !== '' && BusRoutingMap::globMatch($subscription, $destination))
                || ($subscription !== '' && class_exists($destination) && is_a($destination, $subscription, true))) {
                return $node->id;
            }
        }

        return null;
    }

    private static function hasPath(MessageFlowGraph $graph, string $from, string $to): bool
    {
        if ($graph->hasNode($to) && ($graph->node($to)->attributes['owner'] ?? null) === $from) {
            return true;
        }
        $targets = [$to => true];
        $owner = $graph->hasNode($to) ? ($graph->node($to)->attributes['owner'] ?? null) : null;
        if (is_string($owner) && str_starts_with($owner, 'projection:')) {
            $targets[$owner] = true;
        }
        if (str_starts_with($to, 'channel:') && $graph->hasNode($to)) {
            foreach ($graph->edgesFrom($to) as $edge) {
                $targets[$edge->to] = true;
            }
        }
        foreach ($graph->nodes() as $node) {
            if (($node->attributes['owner'] ?? null) === $to) {
                $targets[$node->id] = true;
            }
        }
        $visited = [$from => true];
        $queue = [$from];
        while ($queue !== []) {
            $current = array_shift($queue);
            if (isset($targets[$current])) {
                return true;
            }
            foreach ($graph->edgesFrom($current) as $edge) {
                if (! isset($visited[$edge->to])) {
                    $visited[$edge->to] = true;
                    $queue[] = $edge->to;
                }
            }
        }

        return false;
    }
}
