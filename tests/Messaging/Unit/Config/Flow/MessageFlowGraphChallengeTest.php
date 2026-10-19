<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Flow;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\Flow\FlowEdgeKind;
use Ecotone\Api\Messaging\Flow\FlowNodeKind;
use Ecotone\Api\Messaging\Flow\MessageFlowGraph;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class MessageFlowGraphChallengeTest extends TestCase
{
    public function test_empty_application_excludes_all_framework_nodes(): void
    {
        $graph = EcotoneLite::bootstrapFlowTesting(enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertSame(['nodes' => [], 'edges' => []], $graph->toArray());
    }

    public function test_application_graph_excludes_framework_handlers_and_bus_gateways(): void
    {
        $service = new class () {
            #[CommandHandler('application.handle')]
            public function handle(EventBus $bus): void
            {
                $bus->publishWithRouting('application.event', []);
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $graph->nodesOfKind(FlowNodeKind::COMMAND_HANDLER));
        self::assertSame([], $graph->nodesOfKind(FlowNodeKind::INTERNAL_HANDLER, FlowNodeKind::GATEWAY));
        foreach ($graph->nodes() as $node) {
            self::assertStringStartsNotWith('Ecotone\\', $node->attributes['class'] ?? $node->attributes['interface'] ?? '');
        }
    }
    public function test_repeated_publication_collapses_duplicate_inferred_edges(): void
    {
        $service = new class () {
            #[CommandHandler('duplicates.trigger')]
            public function handle(EventBus $bus): void
            {
                $bus->publishWithRouting('duplicates.event', []);
                $bus->publishWithRouting('duplicates.event', []);
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::EMITS));
    }

    public function test_duplicate_edges_stay_collapsed_after_public_copy_and_reconstruction(): void
    {
        $service = new class () {
            #[CommandHandler('duplicates.copy')]
            public function handle(): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();
        $edge = $graph->edges()[0];

        self::assertCount(1, $graph->withEdge($edge)->edges());
        $data = $graph->toArray();
        $data['edges'][] = $edge->toArray();
        self::assertCount(1, MessageFlowGraph::fromArray($data)->edges());
    }

    public function test_generated_endpoint_ids_do_not_change_the_graph_between_bootstraps(): void
    {
        $service = new class () {
            #[CommandHandler('stable.trigger')]
            public function handle(EventBus $bus): void
            {
                $bus->publishWithRouting('stable.event', []);
            }
        };
        $first = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();
        $second = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertSame($first->toArray(), $second->toArray());
    }

    public function test_anonymous_class_nul_bytes_survive_array_and_json_round_trips(): void
    {
        $service = new class () {
            #[CommandHandler('roundtrip.trigger')]
            public function handle(): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertStringContainsString('\u0000', $graph->toJson());
        self::assertSame($graph->toArray(), MessageFlowGraph::fromJson($graph->toJson())->toArray());
        self::assertSame($graph->toArray(), MessageFlowGraph::fromArray($graph->toArray())->toArray());
    }

    public function test_mermaid_labels_escape_quotes_and_pipes_while_preserving_backslashes(): void
    {
        $service = new class () {
            #[CommandHandler('quote"pipe|back\slash')]
            public function handle(): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertStringContainsString('quote#quot;pipe#124;back\slash', $graph->toMermaid());
    }

}
