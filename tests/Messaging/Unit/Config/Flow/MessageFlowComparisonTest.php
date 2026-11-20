<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Flow;

use Ecotone\Api\Messaging\Flow\FlowChange;
use Ecotone\Api\Messaging\Flow\FlowEdge;
use Ecotone\Api\Messaging\Flow\FlowEdgeKind;
use Ecotone\Api\Messaging\Flow\FlowNode;
use Ecotone\Api\Messaging\Flow\FlowNodeKind;
use Ecotone\Api\Messaging\Flow\MessageFlowGraph;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class MessageFlowComparisonTest extends TestCase
{
    public function test_identical_graphs_have_no_changes(): void
    {
        $comparison = $this->earlierGraph()->compareTo($this->earlierGraph());

        self::assertFalse($comparison->hasChanges());
        self::assertSame("No changes in the message flow.\n", $comparison->toText());
    }

    public function test_the_same_graph_in_another_order_has_no_changes(): void
    {
        $earlier = $this->earlierGraph();
        $reordered = MessageFlowGraph::create(array_reverse($earlier->nodes()), array_reverse($earlier->edges()));

        self::assertFalse($earlier->compareTo($reordered)->hasChanges());
    }

    public function test_attributes_in_another_key_order_are_not_a_change(): void
    {
        $earlier = MessageFlowGraph::create([new FlowNode('channel:orders', FlowNodeKind::CHANNEL, 'orders', ['name' => 'orders', 'pollable' => true])], []);
        $later = MessageFlowGraph::create([new FlowNode('channel:orders', FlowNodeKind::CHANNEL, 'orders', ['pollable' => true, 'name' => 'orders'])], []);

        self::assertFalse($earlier->compareTo($later)->hasChanges());
    }

    public function test_a_node_only_in_the_later_graph_is_added(): void
    {
        $comparison = $this->earlierGraph()->compareTo($this->laterGraph());

        self::assertSame(['handler:Shop\Refund::refund', 'message:order.refund'], $this->idsOf($comparison->addedNodes));
        self::assertSame(FlowChange::ADDED, $comparison->changeOfNode('handler:Shop\Refund::refund'));
    }

    public function test_a_node_only_in_the_earlier_graph_is_removed(): void
    {
        $comparison = $this->earlierGraph()->compareTo($this->laterGraph());

        self::assertSame(['handler:Shop\Audit::record'], $this->idsOf($comparison->removedNodes));
        self::assertSame(FlowChange::REMOVED, $comparison->changeOfNode('handler:Shop\Audit::record'));
    }

    public function test_a_node_with_different_attributes_is_changed_and_keeps_both_states(): void
    {
        $comparison = $this->earlierGraph()->compareTo($this->laterGraph());

        self::assertCount(1, $comparison->changedNodes);
        self::assertSame('handler:Shop\Orders::place', $comparison->changedNodes[0]->after->id);
        self::assertNull($comparison->changedNodes[0]->before->attributes['inputChannel']);
        self::assertSame('orders', $comparison->changedNodes[0]->after->attributes['inputChannel']);
        self::assertSame(FlowChange::CHANGED, $comparison->changeOfNode('handler:Shop\Orders::place'));
        self::assertSame(FlowChange::UNCHANGED, $comparison->changeOfNode('message:order.place'));
    }

    public function test_edges_are_added_and_removed_with_their_nodes_and_between_kept_nodes(): void
    {
        $comparison = $this->earlierGraph()->compareTo($this->laterGraph());

        self::assertSame(
            ['asynchronous channel:orders -> handler:Shop\Orders::place', 'routedTo message:order.refund -> handler:Shop\Refund::refund'],
            $this->edgesOf($comparison->addedEdges),
        );
        self::assertSame(['routedTo message:order.place -> handler:Shop\Audit::record'], $this->edgesOf($comparison->removedEdges));
        self::assertSame(FlowChange::ADDED, $comparison->changeOfEdge(new FlowEdge('channel:orders', 'handler:Shop\Orders::place', FlowEdgeKind::ASYNCHRONOUS)));
        self::assertSame(FlowChange::UNCHANGED, $comparison->changeOfEdge(new FlowEdge('message:order.place', 'handler:Shop\Orders::place', FlowEdgeKind::ROUTED_TO)));
    }

    public function test_an_edge_with_different_attributes_is_changed(): void
    {
        $nodes = [new FlowNode('handler:A::a', FlowNodeKind::COMMAND_HANDLER, 'A::a'), new FlowNode('message:b', FlowNodeKind::EVENT, 'b')];
        $earlier = MessageFlowGraph::create($nodes, [new FlowEdge('handler:A::a', 'message:b', FlowEdgeKind::EMITS, ['via' => 'return'])]);
        $later = MessageFlowGraph::create($nodes, [new FlowEdge('handler:A::a', 'message:b', FlowEdgeKind::EMITS, ['via' => 'eventBus'])]);

        $comparison = $earlier->compareTo($later);

        self::assertCount(1, $comparison->changedEdges);
        self::assertSame('return', $comparison->changedEdges[0]->before->attributes['via']);
        self::assertSame('eventBus', $comparison->changedEdges[0]->after->attributes['via']);
        self::assertSame(FlowChange::CHANGED, $comparison->changeOfEdge($later->edges()[0]));
    }

    public function test_combined_graph_holds_the_later_graph_and_everything_removed_from_the_earlier_one(): void
    {
        $combined = $this->earlierGraph()->compareTo($this->laterGraph())->combinedGraph();

        self::assertTrue($combined->hasNode('handler:Shop\Audit::record'));
        self::assertTrue($combined->hasNode('handler:Shop\Refund::refund'));
        self::assertCount(1, $combined->edgesOfKind(FlowEdgeKind::ROUTED_TO, to: 'handler:Shop\Audit::record'));
        self::assertCount(6, $combined->nodes());
        self::assertCount(4, $combined->edges());
    }

    public function test_text_lists_every_change_with_the_attributes_that_differ(): void
    {
        self::assertSame(
            <<<'TEXT'
                Added (4)
                  + commandHandler handler:Shop\Refund::refund
                  + command message:order.refund
                  + asynchronous channel:orders -> handler:Shop\Orders::place
                  + routedTo message:order.refund -> handler:Shop\Refund::refund
                Removed (2)
                  - eventHandler handler:Shop\Audit::record
                  - routedTo message:order.place -> handler:Shop\Audit::record
                Changed (1)
                  ~ commandHandler handler:Shop\Orders::place
                      inputChannel: null -> "orders"

                TEXT,
            $this->earlierGraph()->compareTo($this->laterGraph())->toText(),
        );
    }

    public function test_array_form_groups_nodes_and_edges_by_change(): void
    {
        $changes = $this->earlierGraph()->compareTo($this->laterGraph())->toArray();

        self::assertSame(['added', 'removed', 'changed'], array_keys($changes));
        self::assertSame('handler:Shop\Refund::refund', $changes['added']['nodes'][0]['id']);
        self::assertSame('handler:Shop\Audit::record', $changes['removed']['nodes'][0]['id']);
        self::assertSame('handler:Shop\Audit::record', $changes['removed']['edges'][0]['to']);
        self::assertNull($changes['changed']['nodes'][0]['before']['attributes']['inputChannel']);
        self::assertSame('orders', $changes['changed']['nodes'][0]['after']['attributes']['inputChannel']);
    }

    public function test_mermaid_draws_the_combined_graph_and_marks_added_removed_and_changed_parts(): void
    {
        $mermaid = $this->earlierGraph()->compareTo($this->laterGraph())->toMermaid();

        self::assertStringStartsWith("flowchart LR\n", $mermaid);
        self::assertStringContainsString('Audit::record', $mermaid);
        self::assertStringContainsString('Refund::refund', $mermaid);
        self::assertMatchesRegularExpression('/^    classDef added /m', $mermaid);
        self::assertMatchesRegularExpression('/^    classDef removed /m', $mermaid);
        self::assertMatchesRegularExpression('/^    classDef changed /m', $mermaid);
        self::assertMatchesRegularExpression('/^    class n\d+(,n\d+)* added$/m', $mermaid);
        self::assertMatchesRegularExpression('/^    class n\d+ removed$/m', $mermaid);
        self::assertMatchesRegularExpression('/^    class n\d+ changed$/m', $mermaid);
        self::assertMatchesRegularExpression('/^    linkStyle \d+(,\d+)* stroke:#2da44e/m', $mermaid);
        self::assertMatchesRegularExpression('/^    linkStyle \d+(,\d+)* stroke:#cf222e/m', $mermaid);
    }

    public function test_mermaid_draws_a_handler_that_became_synchronous_with_its_new_direct_arrow_and_the_channel_arrows_removed(): void
    {
        $nodes = [
            new FlowNode('message:a', FlowNodeKind::COMMAND, 'a'),
            new FlowNode('handler:H::h', FlowNodeKind::COMMAND_HANDLER, 'H::h'),
            new FlowNode('channel:q', FlowNodeKind::CHANNEL, 'q'),
        ];
        $routed = new FlowEdge('message:a', 'handler:H::h', FlowEdgeKind::ROUTED_TO);
        $asynchronous = MessageFlowGraph::create($nodes, [$routed, new FlowEdge('channel:q', 'handler:H::h', FlowEdgeKind::ASYNCHRONOUS)]);
        $synchronous = MessageFlowGraph::create($nodes, [$routed]);

        self::assertSame(
            <<<'MERMAID'
                flowchart LR
                    n0>"a"]
                    n1["Command Handler<br/>H::h"]
                    n2[("Channel<br/>q")]
                    n0 --> n1
                    n0 --> n2
                    n2 ==>|"async"| n1
                    classDef added fill:#dafbe1,stroke:#2da44e,color:#1f2328
                    linkStyle 0 stroke:#2da44e,stroke-width:2px
                    classDef removed fill:#ffebe9,stroke:#cf222e,color:#1f2328,stroke-dasharray:5 5
                    linkStyle 1,2 stroke:#cf222e,stroke-width:2px,stroke-dasharray:5 5

                MERMAID,
            $asynchronous->compareTo($synchronous)->toMermaid(),
        );
        self::assertStringContainsString("    n0 --> n2\n    n0 --> n1\n    n2 ==>|\"async\"| n1\n", $synchronous->compareTo($asynchronous)->toMermaid());
        self::assertStringContainsString("    linkStyle 0,2 stroke:#2da44e,stroke-width:2px\n", $synchronous->compareTo($asynchronous)->toMermaid());
        self::assertStringContainsString("    linkStyle 1 stroke:#cf222e,stroke-width:2px,stroke-dasharray:5 5\n", $synchronous->compareTo($asynchronous)->toMermaid());
    }

    public function test_mermaid_of_identical_graphs_marks_nothing(): void
    {
        $graph = $this->earlierGraph();

        self::assertSame($graph->toMermaid(), $graph->compareTo($graph)->toMermaid());
    }

    private function earlierGraph(): MessageFlowGraph
    {
        return MessageFlowGraph::create(
            [
                new FlowNode('message:order.place', FlowNodeKind::COMMAND, 'order.place'),
                new FlowNode('handler:Shop\Orders::place', FlowNodeKind::COMMAND_HANDLER, 'Orders::place', ['inputChannel' => null]),
                new FlowNode('handler:Shop\Audit::record', FlowNodeKind::EVENT_HANDLER, 'Audit::record'),
                new FlowNode('channel:orders', FlowNodeKind::CHANNEL, 'orders'),
            ],
            [
                new FlowEdge('message:order.place', 'handler:Shop\Orders::place', FlowEdgeKind::ROUTED_TO),
                new FlowEdge('message:order.place', 'handler:Shop\Audit::record', FlowEdgeKind::ROUTED_TO),
            ],
        );
    }

    private function laterGraph(): MessageFlowGraph
    {
        return MessageFlowGraph::create(
            [
                new FlowNode('message:order.place', FlowNodeKind::COMMAND, 'order.place'),
                new FlowNode('handler:Shop\Orders::place', FlowNodeKind::COMMAND_HANDLER, 'Orders::place', ['inputChannel' => 'orders']),
                new FlowNode('channel:orders', FlowNodeKind::CHANNEL, 'orders'),
                new FlowNode('message:order.refund', FlowNodeKind::COMMAND, 'order.refund'),
                new FlowNode('handler:Shop\Refund::refund', FlowNodeKind::COMMAND_HANDLER, 'Refund::refund'),
            ],
            [
                new FlowEdge('message:order.place', 'handler:Shop\Orders::place', FlowEdgeKind::ROUTED_TO),
                new FlowEdge('channel:orders', 'handler:Shop\Orders::place', FlowEdgeKind::ASYNCHRONOUS),
                new FlowEdge('message:order.refund', 'handler:Shop\Refund::refund', FlowEdgeKind::ROUTED_TO),
            ],
        );
    }

    /**
     * @param list<FlowNode> $nodes
     * @return list<string>
     */
    private function idsOf(array $nodes): array
    {
        return array_map(fn (FlowNode $node): string => $node->id, $nodes);
    }

    /**
     * @param list<FlowEdge> $edges
     * @return list<string>
     */
    private function edgesOf(array $edges): array
    {
        return array_map(fn (FlowEdge $edge): string => "{$edge->kind->value} {$edge->from} -> {$edge->to}", $edges);
    }
}
