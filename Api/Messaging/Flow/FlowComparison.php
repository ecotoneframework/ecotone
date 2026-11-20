<?php

declare(strict_types=1);

namespace Ecotone\Api\Messaging\Flow;

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
final class FlowComparison
{
    /**
     * @param list<FlowNode> $addedNodes
     * @param list<FlowNode> $removedNodes
     * @param list<FlowNodeChange> $changedNodes
     * @param list<FlowEdge> $addedEdges
     * @param list<FlowEdge> $removedEdges
     * @param list<FlowEdgeChange> $changedEdges
     */
    private function __construct(
        private readonly MessageFlowGraph $to,
        public readonly array $addedNodes,
        public readonly array $removedNodes,
        public readonly array $changedNodes,
        public readonly array $addedEdges,
        public readonly array $removedEdges,
        public readonly array $changedEdges,
    ) {
    }

    public static function between(MessageFlowGraph $from, MessageFlowGraph $to): self
    {
        $fromNodes = self::nodesById($from);
        $toNodes = self::nodesById($to);
        $fromEdges = self::edgesByIdentity($from);
        $toEdges = self::edgesByIdentity($to);

        $changedNodes = [];
        foreach (array_intersect_key($toNodes, $fromNodes) as $id => $node) {
            if (self::canonical($fromNodes[$id]->toArray()) !== self::canonical($node->toArray())) {
                $changedNodes[] = new FlowNodeChange($fromNodes[$id], $node);
            }
        }
        $changedEdges = [];
        foreach (array_intersect_key($toEdges, $fromEdges) as $identity => $edge) {
            if (self::canonical($fromEdges[$identity]->toArray()) !== self::canonical($edge->toArray())) {
                $changedEdges[] = new FlowEdgeChange($fromEdges[$identity], $edge);
            }
        }

        return new self(
            to: $to,
            addedNodes: array_values(array_diff_key($toNodes, $fromNodes)),
            removedNodes: array_values(array_diff_key($fromNodes, $toNodes)),
            changedNodes: $changedNodes,
            addedEdges: array_values(array_diff_key($toEdges, $fromEdges)),
            removedEdges: array_values(array_diff_key($fromEdges, $toEdges)),
            changedEdges: $changedEdges,
        );
    }

    public function hasChanges(): bool
    {
        return $this->addedNodes !== [] || $this->removedNodes !== [] || $this->changedNodes !== [] || $this->addedEdges !== [] || $this->removedEdges !== [] || $this->changedEdges !== [];
    }

    public function changeOfNode(string $id): FlowChange
    {
        return match (true) {
            self::holdsNode($this->addedNodes, $id) => FlowChange::ADDED,
            self::holdsNode($this->removedNodes, $id) => FlowChange::REMOVED,
            self::holdsNode(array_map(fn (FlowNodeChange $change): FlowNode => $change->after, $this->changedNodes), $id) => FlowChange::CHANGED,
            default => FlowChange::UNCHANGED,
        };
    }

    public function changeOfEdge(FlowEdge $edge): FlowChange
    {
        $identity = $edge->identity();

        return match (true) {
            self::holdsEdge($this->addedEdges, $identity) => FlowChange::ADDED,
            self::holdsEdge($this->removedEdges, $identity) => FlowChange::REMOVED,
            self::holdsEdge(array_map(fn (FlowEdgeChange $change): FlowEdge => $change->after, $this->changedEdges), $identity) => FlowChange::CHANGED,
            default => FlowChange::UNCHANGED,
        };
    }

    public function combinedGraph(): MessageFlowGraph
    {
        return MessageFlowGraph::create([...$this->to->nodes(), ...$this->removedNodes], [...$this->to->edges(), ...$this->removedEdges]);
    }

    /**
     * @return array{added: array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>}, removed: array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>}, changed: array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>}}
     */
    public function toArray(): array
    {
        return [
            'added' => [
                'nodes' => array_map(fn (FlowNode $node): array => $node->toArray(), $this->addedNodes),
                'edges' => array_map(fn (FlowEdge $edge): array => $edge->toArray(), $this->addedEdges),
            ],
            'removed' => [
                'nodes' => array_map(fn (FlowNode $node): array => $node->toArray(), $this->removedNodes),
                'edges' => array_map(fn (FlowEdge $edge): array => $edge->toArray(), $this->removedEdges),
            ],
            'changed' => [
                'nodes' => array_map(fn (FlowNodeChange $change): array => $change->toArray(), $this->changedNodes),
                'edges' => array_map(fn (FlowEdgeChange $change): array => $change->toArray(), $this->changedEdges),
            ],
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    public function toText(): string
    {
        if (! $this->hasChanges()) {
            return "No changes in the message flow.\n";
        }

        $lines = [];
        if ($this->addedNodes !== [] || $this->addedEdges !== []) {
            $lines[] = sprintf('Added (%d)', count($this->addedNodes) + count($this->addedEdges));
            foreach ($this->addedNodes as $node) {
                $lines[] = '  + ' . self::nodeLine($node);
            }
            foreach ($this->addedEdges as $edge) {
                $lines[] = '  + ' . self::edgeLine($edge);
            }
        }
        if ($this->removedNodes !== [] || $this->removedEdges !== []) {
            $lines[] = sprintf('Removed (%d)', count($this->removedNodes) + count($this->removedEdges));
            foreach ($this->removedNodes as $node) {
                $lines[] = '  - ' . self::nodeLine($node);
            }
            foreach ($this->removedEdges as $edge) {
                $lines[] = '  - ' . self::edgeLine($edge);
            }
        }
        if ($this->changedNodes !== [] || $this->changedEdges !== []) {
            $lines[] = sprintf('Changed (%d)', count($this->changedNodes) + count($this->changedEdges));
            foreach ($this->changedNodes as $change) {
                $lines[] = '  ~ ' . self::nodeLine($change->after);
                $lines = [...$lines, ...self::differenceLines(
                    ['kind' => $change->before->kind->value, 'label' => $change->before->label, ...$change->before->attributes],
                    ['kind' => $change->after->kind->value, 'label' => $change->after->label, ...$change->after->attributes],
                )];
            }
            foreach ($this->changedEdges as $change) {
                $lines[] = '  ~ ' . self::edgeLine($change->after);
                $lines = [...$lines, ...self::differenceLines($change->before->attributes, $change->after->attributes)];
            }
        }

        return implode("\n", $lines) . "\n";
    }

    public function toMermaid(): string
    {
        return $this->combinedGraph()->toMermaidWithChangesOf($this);
    }

    /**
     * @return array<string, FlowNode>
     */
    private static function nodesById(MessageFlowGraph $graph): array
    {
        $nodes = [];
        foreach ($graph->nodes() as $node) {
            $nodes[$node->id] = $node;
        }
        ksort($nodes, SORT_STRING);

        return $nodes;
    }

    /**
     * @return array<string, FlowEdge>
     */
    private static function edgesByIdentity(MessageFlowGraph $graph): array
    {
        $edges = [];
        foreach ($graph->edges() as $edge) {
            $edges[$edge->identity()] = $edge;
        }
        ksort($edges, SORT_STRING);

        return $edges;
    }

    private static function canonical(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        $value = array_map(self::canonical(...), $value);
        if (! array_is_list($value)) {
            ksort($value, SORT_STRING);
        }

        return $value;
    }

    /**
     * @param list<FlowNode> $nodes
     */
    private static function holdsNode(array $nodes, string $id): bool
    {
        foreach ($nodes as $node) {
            if ($node->id === $id) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<FlowEdge> $edges
     */
    private static function holdsEdge(array $edges, string $identity): bool
    {
        foreach ($edges as $edge) {
            if ($edge->identity() === $identity) {
                return true;
            }
        }

        return false;
    }

    private static function nodeLine(FlowNode $node): string
    {
        return $node->kind->value . ' ' . $node->id;
    }

    private static function edgeLine(FlowEdge $edge): string
    {
        return "{$edge->kind->value} {$edge->from} -> {$edge->to}";
    }

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     * @return list<string>
     */
    private static function differenceLines(array $before, array $after): array
    {
        $names = array_keys($before + $after);
        sort($names, SORT_STRING);
        $lines = [];
        foreach ($names as $name) {
            $valueBefore = self::canonical($before[$name] ?? null);
            $valueAfter = self::canonical($after[$name] ?? null);
            if ($valueBefore !== $valueAfter) {
                $lines[] = sprintf('      %s: %s -> %s', $name, self::printable($valueBefore), self::printable($valueAfter));
            }
        }

        return $lines;
    }

    private static function printable(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
