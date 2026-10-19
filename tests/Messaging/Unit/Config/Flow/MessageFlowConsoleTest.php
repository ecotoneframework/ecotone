<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Flow;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\Flow\MessageFlowGraph;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class MessageFlowConsoleTest extends TestCase
{
    public function test_empty_application_dump_is_an_empty_mermaid_flowchart(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE);

        $ecotone->runConsoleCommand('ecotone:flow:dump');

        self::assertSame("flowchart LR\n", $ecotone->getInMemoryConsoleWriter()->getWrittenContent());
    }

    public function test_empty_application_json_dump_has_empty_node_and_edge_lists(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE);

        $ecotone->runConsoleCommand('ecotone:flow:dump', ['format' => 'json']);

        self::assertSame(['nodes' => [], 'edges' => []], json_decode($ecotone->getInMemoryConsoleWriter()->getWrittenContent(), true, flags: JSON_THROW_ON_ERROR));
    }

    public function test_json_dump_reconstructs_the_application_graph_with_anonymous_names(): void
    {
        $service = new class () {
            #[CommandHandler('console.handle')]
            public function handle(): void
            {
            }
        };
        $ecotone = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE);

        $ecotone->runConsoleCommand('ecotone:flow:dump', ['format' => 'json']);

        self::assertSame($ecotone->getMessageFlowGraph()->toArray(), MessageFlowGraph::fromJson($ecotone->getInMemoryConsoleWriter()->getWrittenContent())->toArray());
    }

    public function test_unknown_dump_format_explains_both_supported_formats(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('ecotone:flow:dump was asked for format `xml`, which it cannot produce. Pass --format=mermaid for a Mermaid flowchart to paste into a diagram viewer, or --format=json for the graph as nodes and edges.');

        $ecotone->runConsoleCommand('ecotone:flow:dump', ['format' => 'xml']);
    }
}
