<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Flow;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Distributed;
use Ecotone\Api\Gateway\DistributedBus;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\Flow\FlowEdgeKind;
use Ecotone\Api\Messaging\Flow\FlowNodeKind;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class MessageFlowDistributedTest extends TestCase
{
    public function test_custom_distributed_reference_reaches_a_handler_that_publishes_locally(): void
    {
        $service = new class () {
            #[Distributed('partner.bus')]
            #[CommandHandler('remote.receive')]
            public function receive(array $payload, EventBus $bus): void
            {
                $bus->publish(new LocalEventForDistribution());
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $handler = 'handler:' . $service::class . '::receive';
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::DISTRIBUTES, 'distributed:partner.bus', 'message:remote.receive'));
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::ROUTED_TO, 'message:remote.receive', $handler));
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::EMITS, $handler, 'message:' . LocalEventForDistribution::class));
    }

    public function test_dynamic_distributed_destination_keeps_the_bus_edge_without_guessing_a_message(): void
    {
        $service = new class () {
            #[CommandHandler('remote.send')]
            public function send(array $payload, DistributedBus $bus): void
            {
                $bus->sendCommand($payload['service'], $payload['route'], '{}');
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $edges = $graph->edgesOfKind(FlowEdgeKind::SENDS_TO, 'handler:' . $service::class . '::send', 'distributed:' . DistributedBus::class));
        self::assertSame(['inferred' => true, 'via' => 'sendCommand'], $edges[0]->attributes);
        self::assertCount(1, $graph->nodesOfKind(FlowNodeKind::COMMAND));
    }
}

final class LocalEventForDistribution
{
}
