<?php

/*
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 */
declare(strict_types=1);

namespace Ecotone\Messaging\Config\Flow;

use Ecotone\Api\Messaging\Flow\FlowEdge;
use Ecotone\Api\Messaging\Flow\FlowNode;
use Ecotone\Api\Messaging\Flow\FlowNodeKind;
use Ecotone\Api\Messaging\Flow\MessageFlowGraph;

final class InferredFlow
{
    /** @var array<string, FlowNode> */
    private array $nodes = [];
    /** @var list<FlowEdge> */
    private array $edges;

    public function __construct(MessageFlowGraph $declared)
    {
        foreach ($declared->nodes() as $node) {
            $this->nodes[$node->id] = $node;
        }
        $this->edges = $declared->edges();
    }

    public function hasNode(string $id): bool
    {
        return isset($this->nodes[$id]);
    }

    public function node(string $id): FlowNode
    {
        return $this->nodes[$id];
    }

    public function addNode(FlowNode $node): void
    {
        $this->nodes[$node->id] = $node;
    }

    public function addEdge(FlowEdge $edge): void
    {
        $this->edges[] = $edge;
    }

    public function graph(): MessageFlowGraph
    {
        $connected = [];
        foreach ($this->edges as $edge) {
            $connected[$edge->from] = true;
            $connected[$edge->to] = true;
        }

        return MessageFlowGraph::create(
            array_values(array_filter($this->nodes, fn (FlowNode $node) => $node->kind !== FlowNodeKind::CHANNEL || isset($connected[$node->id]))),
            $this->edges,
        );
    }
}
