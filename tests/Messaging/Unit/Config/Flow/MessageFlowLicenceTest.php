<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Flow;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\Flow\FlowEdgeKind;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class MessageFlowLicenceTest extends TestCase
{
    private const REFUSAL = 'The message flow graph (ConfiguredMessagingSystem::getMessageFlowGraph() and the ecotone:flow:dump console command) is part of Ecotone Enterprise. Provide your licence key as `licenceKey` in the Symfony, Laravel or Tempest Ecotone configuration, or as the `licenceKey:` argument of EcotoneLite, or start a trial at https://ecotone.tech/pricing. See https://docs.ecotone.tech/enterprise for details.';

    public function test_message_flow_graph_without_enterprise_licence_is_refused_with_the_way_to_enable_it(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(enabledModules: []);

        $this->expectException(LicensingException::class);
        $this->expectExceptionMessage(self::REFUSAL);

        $ecotone->getMessageFlowGraph();
    }

    public function test_flow_dump_command_is_not_registered_without_enterprise_licence(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(enabledModules: []);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Console command `ecotone:flow:dump` is not registered.');

        $ecotone->runConsoleCommand('ecotone:flow:dump');
    }

    public function test_application_without_enterprise_licence_still_bootstraps_and_handles_messages(): void
    {
        $service = new class () {
            public bool $handled = false;

            #[CommandHandler('licence.check')]
            public function handle(): void
            {
                $this->handled = true;
            }
        };

        EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [])->sendCommandWithRouting('licence.check');

        self::assertTrue($service->handled);
    }

    public function test_message_flow_graph_with_enterprise_licence_shows_the_application(): void
    {
        $service = new class () {
            #[CommandHandler('licence.graph')]
            public function handle(): void
            {
            }
        };

        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::ROUTED_TO, 'message:licence.graph', 'handler:' . $service::class . '::handle'));
    }
}
