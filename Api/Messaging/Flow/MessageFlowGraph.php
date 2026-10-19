<?php

declare(strict_types=1);

namespace Ecotone\Api\Messaging\Flow;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
final class MessageFlowGraph implements DefinedObject
{
    /**
     * @param array<string, FlowNode> $nodes
     * @param list<FlowEdge> $edges
     */
    private function __construct(private array $nodes, private array $edges)
    {
    }

    /**
     * @param list<FlowNode> $nodes
     * @param list<FlowEdge> $edges
     */
    public static function create(array $nodes, array $edges): self
    {
        $graph = new self([], []);
        foreach ($nodes as $node) {
            $graph->nodes[$node->id] = $node;
        }
        $seenEdges = [];
        foreach ($edges as $edge) {
            $graph->nodes[$edge->from] ?? throw InvalidArgumentException::create("Flow edge {$edge->kind->value} starts at `{$edge->from}`, which is not a node of this graph. Add the node first with MessageFlowGraph::create() or withNode().");
            $graph->nodes[$edge->to] ?? throw InvalidArgumentException::create("Flow edge {$edge->kind->value} ends at `{$edge->to}`, which is not a node of this graph. Add the node first with MessageFlowGraph::create() or withNode().");
            $key = $edge->kind->value . '|' . strlen($edge->from) . '|' . $edge->from . $edge->to . '|' . ($edge->attributes['targetService'] ?? '');
            if (! isset($seenEdges[$key])) {
                $graph->edges[] = $edge;
                $seenEdges[$key] = true;
            }
        }

        return $graph;
    }

    /**
     * @param array{nodes: list<array{id: string, kind: string, label: string, attributes: array<string, mixed>}>, edges: list<array{from: string, to: string, kind: string, attributes: array<string, mixed>}>} $data
     */
    public static function fromArray(array $data): self
    {
        return self::create(
            array_map(fn (array $node) => FlowNode::fromArray($node), $data['nodes']),
            array_map(fn (array $edge) => FlowEdge::fromArray($edge), $data['edges']),
        );
    }

    public static function fromJson(string $json): self
    {
        return self::fromArray(json_decode($json, true, flags: JSON_THROW_ON_ERROR));
    }

    public function hasNode(string $id): bool
    {
        return isset($this->nodes[$id]);
    }

    public function node(string $id): FlowNode
    {
        return $this->nodes[$id] ?? throw InvalidArgumentException::create("Message flow graph has no node `{$id}`. Known nodes: " . implode(', ', array_keys($this->nodes)) . '. Check it with hasNode() first, or list the nodes of a kind with nodesOfKind().');
    }

    /**
     * @return list<FlowNode>
     */
    public function nodes(): array
    {
        return array_values($this->nodes);
    }

    /**
     * @return list<FlowNode>
     */
    public function nodesOfKind(FlowNodeKind ...$kinds): array
    {
        return array_values(array_filter($this->nodes, fn (FlowNode $node) => in_array($node->kind, $kinds, true)));
    }

    /**
     * @return list<FlowEdge>
     */
    public function edges(): array
    {
        return $this->edges;
    }

    /**
     * @return list<FlowEdge>
     */
    public function edgesFrom(string $id): array
    {
        return array_values(array_filter($this->edges, fn (FlowEdge $edge) => $edge->from === $id));
    }

    /**
     * @return list<FlowEdge>
     */
    public function edgesTo(string $id): array
    {
        return array_values(array_filter($this->edges, fn (FlowEdge $edge) => $edge->to === $id));
    }

    /**
     * @return list<FlowEdge>
     */
    public function edgesOfKind(FlowEdgeKind $kind, ?string $from = null, ?string $to = null): array
    {
        return array_values(array_filter(
            $this->edges,
            fn (FlowEdge $edge) => $edge->kind === $kind && ($from === null || $edge->from === $from) && ($to === null || $edge->to === $to)
        ));
    }

    public function withNode(FlowNode $node): self
    {
        $nodes = $this->nodes;
        $nodes[$node->id] = $node;

        return new self($nodes, $this->edges);
    }

    public function withEdge(FlowEdge $edge): self
    {
        return self::create(array_values($this->nodes), [...$this->edges, $edge]);
    }

    /**
     * @return array{nodes: list<array{id: string, kind: string, label: string, attributes: array<string, mixed>}>, edges: list<array{from: string, to: string, kind: string, attributes: array<string, mixed>}>}
     */
    public function toArray(): array
    {
        return [
            'nodes' => array_map(fn (FlowNode $node) => $node->toArray(), array_values($this->nodes)),
            'edges' => array_map(fn (FlowEdge $edge) => $edge->toArray(), $this->edges),
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    public function toMermaid(): string
    {
        $lines = ['flowchart LR'];
        $mermaidIds = [];
        $index = 0;
        foreach ($this->nodes as $node) {
            $mermaidIds[$node->id] = 'n' . $index++;
        }

        $ownedNodeIds = [];
        foreach ($this->nodesOfKind(FlowNodeKind::AGGREGATE, FlowNodeKind::SAGA, FlowNodeKind::PROJECTION) as $owner) {
            $members = array_values(array_filter($this->nodes, fn (FlowNode $node) => ($node->attributes['owner'] ?? null) === $owner->id));
            if ($members === []) {
                $lines[] = '    ' . $this->mermaidNode($mermaidIds[$owner->id], $owner);
                continue;
            }
            $lines[] = sprintf('    subgraph %s ["%s"]', $mermaidIds[$owner->id], $this->mermaidText($this->kindTitle($owner->kind) . ': ' . $owner->label));
            foreach ($members as $member) {
                $lines[] = '        ' . $this->mermaidNode($mermaidIds[$member->id], $member);
                $ownedNodeIds[$member->id] = true;
            }
            $lines[] = '    end';
            $ownedNodeIds[$owner->id] = true;
        }
        foreach ($this->nodes as $node) {
            if (isset($ownedNodeIds[$node->id])) {
                continue;
            }
            $lines[] = '    ' . $this->mermaidNode($mermaidIds[$node->id], $node);
        }
        $edgeLines = [];
        foreach ($this->edges as $edge) {
            $asynchronousChannels = $edge->kind === FlowEdgeKind::ROUTED_TO ? $this->edgesOfKind(FlowEdgeKind::ASYNCHRONOUS, to: $edge->to) : [];
            if ($asynchronousChannels === []) {
                $edgeLines[] = sprintf('    %s %s %s', $mermaidIds[$edge->from], $this->mermaidArrow($edge), $mermaidIds[$edge->to]);

                continue;
            }
            foreach ($asynchronousChannels as $asynchronousChannel) {
                $edgeLines[] = sprintf('    %s %s %s', $mermaidIds[$edge->from], $this->mermaidArrow($edge), $mermaidIds[$asynchronousChannel->from]);
            }
        }

        return implode("\n", [...$lines, ...array_values(array_unique($edgeLines))]) . "\n";
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)], 'fromJson');
    }

    private function mermaidNode(string $mermaidId, FlowNode $node): string
    {
        $label = $this->mermaidText($node->kind->isHandler() || $node->kind === FlowNodeKind::INTERCEPTOR ? $this->kindTitle($node->kind) . '<br/>' . $node->label : $node->label);

        return $mermaidId . match ($node->kind) {
            FlowNodeKind::COMMAND => sprintf('>"%s"]', $label),
            FlowNodeKind::EVENT => sprintf('(["%s"])', $label),
            FlowNodeKind::QUERY => sprintf('{{"%s"}}', $label),
            FlowNodeKind::CHANNEL => sprintf('[("Channel<br/>%s")]', $label),
            FlowNodeKind::GATEWAY => sprintf('[["%s"]]', $label),
            FlowNodeKind::ROUTER, FlowNodeKind::ORCHESTRATOR => sprintf('{"%s"}', $label),
            FlowNodeKind::SCHEDULED => sprintf('(("%s"))', $label),
            FlowNodeKind::DISTRIBUTED_BUS => sprintf('[\\"%s"\\]', $label),
            FlowNodeKind::AGGREGATE, FlowNodeKind::SAGA, FlowNodeKind::PROJECTION => sprintf('[/"%s"/]', $this->mermaidText($this->kindTitle($node->kind) . ': ' . $node->label)),
            default => sprintf('["%s"]', $label),
        };
    }

    private function mermaidArrow(FlowEdge $edge): string
    {
        $label = match ($edge->kind) {
            FlowEdgeKind::EMITS => 'emits (' . ($edge->attributes['via'] ?? 'new') . ')',
            FlowEdgeKind::ASYNCHRONOUS => 'async',
            FlowEdgeKind::OUTPUT => 'output',
            FlowEdgeKind::APPLIES => 'applies',
            FlowEdgeKind::CALLS => 'calls',
            FlowEdgeKind::ROUTES_TO => 'routes',
            FlowEdgeKind::DISTRIBUTES => 'distributed',
            FlowEdgeKind::INTERCEPTED_BY => 'intercepted by',
            FlowEdgeKind::SENDS_TO => $edge->isInferred() ? 'sends (' . ($edge->attributes['via'] ?? '') . ')' : (isset($edge->attributes['targetService']) ? 'to ' . $edge->attributes['targetService'] : ''),
            default => '',
        };
        $labelPart = $label === '' ? '' : '|"' . $this->mermaidText($label) . '"|';
        if ($edge->kind === FlowEdgeKind::ASYNCHRONOUS) {
            return '==>' . $labelPart;
        }

        return ($edge->isInferred() ? '-.->' : '-->') . $labelPart;
    }

    private function kindTitle(FlowNodeKind $kind): string
    {
        return match ($kind) {
            FlowNodeKind::COMMAND_HANDLER => 'Command Handler',
            FlowNodeKind::EVENT_HANDLER => 'Event Handler',
            FlowNodeKind::QUERY_HANDLER => 'Query Handler',
            FlowNodeKind::INTERNAL_HANDLER => 'Internal Handler',
            FlowNodeKind::ROUTER => 'Router',
            FlowNodeKind::AGGREGATE => 'Aggregate',
            FlowNodeKind::SAGA => 'Saga',
            FlowNodeKind::PROJECTION => 'Projection',
            default => ucfirst($kind->value),
        };
    }

    private function mermaidText(string $text): string
    {
        return str_replace(['"', '|'], ['#quot;', '#124;'], $text);
    }
}
