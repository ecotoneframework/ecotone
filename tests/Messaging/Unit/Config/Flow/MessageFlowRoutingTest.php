<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Flow;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\Flow\FlowEdgeKind;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class MessageFlowRoutingTest extends TestCase
{
    public function test_emitted_event_reaches_interface_subscriber(): void
    {
        $service = new class () {
            #[CommandHandler('routing.publish')]
            public function publish(EventBus $bus): void
            {
                $bus->publish(new RoutingEvent());
            }

            #[EventHandler]
            public function subscribe(RoutingEventContract $event): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::ROUTED_TO, 'message:' . RoutingEvent::class, 'handler:' . $service::class . '::subscribe'));
        self::assertTrue($graph->node('message:' . RoutingEvent::class)->attributes['handled']);
    }

    public function test_emitted_event_reaches_parent_subscriber(): void
    {
        $service = new class () {
            #[CommandHandler('routing.publish')]
            public function publish(EventBus $bus): void
            {
                $bus->publish(new RoutingEvent());
            }

            #[EventHandler]
            public function subscribe(RoutingParentEvent $event): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::ROUTED_TO, 'message:' . RoutingEvent::class, 'handler:' . $service::class . '::subscribe'));
        self::assertTrue($graph->node('message:' . RoutingEvent::class)->attributes['handled']);
    }

    public function test_emitted_event_reaches_object_subscriber(): void
    {
        $service = new class () {
            #[CommandHandler('routing.publish')]
            public function publish(EventBus $bus): void
            {
                $bus->publish(new RoutingEvent());
            }

            #[EventHandler]
            public function subscribe(object $event): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::ROUTED_TO, 'message:' . RoutingEvent::class, 'handler:' . $service::class . '::subscribe'));
        self::assertTrue($graph->node('message:' . RoutingEvent::class)->attributes['handled']);
    }

    public function test_emitted_event_reaches_union_subscriber(): void
    {
        $service = new class () {
            #[CommandHandler('routing.publish')]
            public function publish(EventBus $bus): void
            {
                $bus->publish(new RoutingEvent());
            }

            #[EventHandler]
            public function subscribe(RoutingEvent|OtherRoutingEvent $event): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::ROUTED_TO, 'message:' . RoutingEvent::class, 'handler:' . $service::class . '::subscribe'));
        self::assertTrue($graph->node('message:' . RoutingEvent::class)->attributes['handled']);
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::ROUTED_TO, 'message:' . OtherRoutingEvent::class, 'handler:' . $service::class . '::subscribe'));
    }

    public function test_named_event_reaches_array_catch_all_subscriber(): void
    {
        $service = new class () {
            #[CommandHandler('routing.publish')]
            public function publish(EventBus $bus): void
            {
                $bus->publishWithRouting('routing.created', []);
            }

            #[EventHandler('*')]
            public function subscribe(array $event): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::ROUTED_TO, 'message:routing.created', 'handler:' . $service::class . '::subscribe'));
        self::assertTrue($graph->node('message:routing.created')->attributes['handled']);
    }
    public function test_one_event_reaches_every_registered_handler(): void
    {
        $service = new class () {
            #[EventHandler]
            public function first(RoutingEvent $event): void
            {
            }

            #[EventHandler]
            public function second(RoutingEvent $event): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(2, $graph->edgesOfKind(FlowEdgeKind::ROUTED_TO, 'message:' . RoutingEvent::class));
    }

    public function test_repeated_event_attributes_preserve_all_routing_keys(): void
    {
        $service = new class () {
            #[EventHandler('event.first', endpointId: 'first')]
            #[EventHandler('event.second', endpointId: 'second')]
            public function handle(array $event): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $id = 'handler:' . $service::class . '::handle';
        self::assertEqualsCanonicalizing(['event.first', 'event.second'], $graph->node($id)->attributes['routingKeys']);
        self::assertCount(2, $graph->edgesTo($id));
    }

    public function test_command_and_event_attributes_show_both_handler_roles(): void
    {
        $service = new class () {
            #[CommandHandler('dual.command', endpointId: 'dual.command.handler')]
            #[EventHandler('dual.event', endpointId: 'dual.event.handler')]
            public function handle(array $payload): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $commands = $graph->nodesOfKind(\Ecotone\Api\Messaging\Flow\FlowNodeKind::COMMAND_HANDLER));
        self::assertCount(1, $events = $graph->nodesOfKind(\Ecotone\Api\Messaging\Flow\FlowNodeKind::EVENT_HANDLER));
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::ROUTED_TO, 'message:dual.command', $commands[0]->id));
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::ROUTED_TO, 'message:dual.event', $events[0]->id));
    }

    public function test_glob_subscription_matches_dotted_keys_without_treating_dots_as_wildcards(): void
    {
        $service = new class () {
            #[CommandHandler('glob.publish')]
            public function publish(EventBus $bus): void
            {
                $bus->publishWithRouting('orders.created.v2', []);
                $bus->publishWithRouting('ordersXcreated', []);
            }

            #[EventHandler('orders.*')]
            public function subscribe(array $event): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $id = 'handler:' . $service::class . '::subscribe';
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::ROUTED_TO, 'message:orders.created.v2', $id));
        self::assertSame([], $graph->edgesOfKind(FlowEdgeKind::ROUTED_TO, 'message:ordersXcreated', $id));
        self::assertTrue($graph->hasNode('message:orders.*'));
    }

}

interface RoutingEventContract
{
}

abstract class RoutingParentEvent
{
}

final class RoutingEvent extends RoutingParentEvent implements RoutingEventContract
{
}

final class OtherRoutingEvent
{
}
