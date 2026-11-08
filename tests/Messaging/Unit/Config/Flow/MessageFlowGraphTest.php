<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Flow;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Distributed;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\MessageGateway;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Attribute\Router;
use Ecotone\Api\ExtensionObject\DistributedServiceMap;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Gateway\DistributedBus;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\Flow\FlowEdge;
use Ecotone\Api\Messaging\Flow\FlowEdgeKind;
use Ecotone\Api\Messaging\Flow\FlowNodeKind;
use Ecotone\Api\Messaging\Flow\MessageFlowGraph;
use Ecotone\Api\Modelling\WithAggregateVersioning;
use Ecotone\Api\Modelling\WithEvents;
use Ecotone\Api\Projecting\FromStream;
use Ecotone\Api\Projecting\Projection;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class MessageFlowGraphTest extends TestCase
{
    public function test_command_routed_by_class_reaches_service_handler_whose_output_channel_feeds_an_internal_handler(): void
    {
        $service = new class () {
            #[CommandHandler(endpointId: 'order.place', outputChannelName: 'order.placed.pipeline')]
            public function place(PlaceOrderForFlowGraph $command): string
            {
                return $command->orderId;
            }

            #[InternalHandler(inputChannelName: 'order.placed.pipeline', endpointId: 'order.notify')]
            public function notify(string $orderId): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $handlerId = 'handler:' . $service::class . '::place';
        $internalId = 'handler:' . $service::class . '::notify';

        self::assertSame(FlowNodeKind::COMMAND, $graph->node('message:' . PlaceOrderForFlowGraph::class)->kind);
        self::assertSame(FlowNodeKind::COMMAND_HANDLER, $graph->node($handlerId)->kind);
        self::assertSame('order.place', $graph->node($handlerId)->attributes['endpointId']);
        self::assertSame(FlowNodeKind::INTERNAL_HANDLER, $graph->node($internalId)->kind);
        self::assertSame(FlowNodeKind::CHANNEL, $graph->node('channel:order.placed.pipeline')->kind);
        self::assertEdges([
            [FlowEdgeKind::ROUTED_TO, 'message:' . PlaceOrderForFlowGraph::class, $handlerId],
            [FlowEdgeKind::OUTPUT, $handlerId, 'channel:order.placed.pipeline'],
            [FlowEdgeKind::INPUT, 'channel:order.placed.pipeline', $internalId],
        ], $graph);
    }

    public function test_command_and_query_routed_by_routing_key(): void
    {
        $service = new class () {
            #[CommandHandler('order.cancel', endpointId: 'order.cancel.handler')]
            public function cancel(array $payload): void
            {
            }

            #[QueryHandler('order.status', endpointId: 'order.status.handler')]
            public function status(array $payload): string
            {
                return 'placed';
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertSame(FlowNodeKind::COMMAND, $graph->node('message:order.cancel')->kind);
        self::assertSame(FlowNodeKind::QUERY, $graph->node('message:order.status')->kind);
        self::assertSame(FlowNodeKind::QUERY_HANDLER, $graph->node('handler:' . $service::class . '::status')->kind);
        self::assertEdges([
            [FlowEdgeKind::ROUTED_TO, 'message:order.cancel', 'handler:' . $service::class . '::cancel'],
            [FlowEdgeKind::ROUTED_TO, 'message:order.status', 'handler:' . $service::class . '::status'],
        ], $graph);
    }

    public function test_asynchronous_handler_executes_through_pollable_channel(): void
    {
        $service = new class () {
            #[Asynchronous('orders')]
            #[EventHandler(endpointId: 'order.placed.mail')]
            public function mail(OrderPlacedForFlowGraph $event): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting(
            [$service::class],
            [$service],
            [SimpleMessageChannelBuilder::createQueueChannel('orders')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        )->getMessageFlowGraph();

        $handlerId = 'handler:' . $service::class . '::mail';
        self::assertSame(FlowNodeKind::EVENT, $graph->node('message:' . OrderPlacedForFlowGraph::class)->kind);
        self::assertTrue($graph->node('channel:orders')->attributes['pollable']);
        self::assertEdges([
            [FlowEdgeKind::ROUTED_TO, 'message:' . OrderPlacedForFlowGraph::class, $handlerId],
            [FlowEdgeKind::ASYNCHRONOUS, 'channel:orders', $handlerId],
        ], $graph);
    }

    public function test_class_level_asynchronous_applies_to_every_handler_of_the_class_except_queries(): void
    {
        $service = new #[Asynchronous('orders')] class () {
            #[CommandHandler(endpointId: 'order.place')]
            public function place(PlaceOrderForFlowGraph $command): void
            {
            }

            #[QueryHandler(endpointId: 'order.find')]
            public function find(FindOrderForFlowGraph $query): string
            {
                return 'placed';
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting(
            [$service::class],
            [$service],
            [SimpleMessageChannelBuilder::createQueueChannel('orders')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        )->getMessageFlowGraph();

        self::assertEdges([[FlowEdgeKind::ASYNCHRONOUS, 'channel:orders', 'handler:' . $service::class . '::place']], $graph);
        self::assertSame([], $graph->edgesOfKind(FlowEdgeKind::ASYNCHRONOUS, to: 'handler:' . $service::class . '::find'));
    }

    public function test_event_sourced_aggregate_applies_declared_events_and_handlers_belong_to_the_aggregate(): void
    {
        $aggregate = new #[EventSourcingAggregate] class () {
            use WithAggregateVersioning;

            #[Identifier]
            private string $orderId = '';

            #[CommandHandler]
            public static function place(PlaceOrderForFlowGraph $command): array
            {
                return [new OrderPlacedForFlowGraph($command->orderId)];
            }

            #[Apply]
            public function whenPlaced(OrderPlacedForFlowGraph $event): void
            {
                $this->orderId = $event->orderId;
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$aggregate::class], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $aggregateId = 'aggregate:' . $aggregate::class;
        $handlerId = 'handler:' . $aggregate::class . '::place';
        self::assertSame(FlowNodeKind::AGGREGATE, $graph->node($aggregateId)->kind);
        self::assertTrue($graph->node($aggregateId)->attributes['eventSourced']);
        self::assertSame($aggregateId, $graph->node($handlerId)->attributes['owner']);
        self::assertEdges([
            [FlowEdgeKind::APPLIES, $aggregateId, 'message:' . OrderPlacedForFlowGraph::class],
            [FlowEdgeKind::EMITS, $handlerId, 'message:' . OrderPlacedForFlowGraph::class],
        ], $graph);
        self::assertSame('return', $graph->edgesOfKind(FlowEdgeKind::EMITS, from: $handlerId)[0]->attributes['via']);
    }

    public function test_state_stored_aggregate_recording_events_is_inferred_from_its_source(): void
    {
        $aggregate = new #[Aggregate] class () {
            use WithEvents;

            #[Identifier]
            private string $orderId = '';

            #[CommandHandler]
            public static function place(PlaceOrderForFlowGraph $command): static
            {
                $order = new static();
                $order->orderId = $command->orderId;
                $order->recordThat(new OrderPlacedForFlowGraph($command->orderId));

                return $order;
            }
        };
        $subscriber = new class () {
            #[EventHandler(endpointId: 'order.placed.audit')]
            public function audit(OrderPlacedForFlowGraph $event): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$aggregate::class, $subscriber::class], [$subscriber], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $handlerId = 'handler:' . $aggregate::class . '::place';
        self::assertFalse($graph->node('aggregate:' . $aggregate::class)->attributes['eventSourced']);
        self::assertEdges([
            [FlowEdgeKind::EMITS, $handlerId, 'message:' . OrderPlacedForFlowGraph::class],
            [FlowEdgeKind::ROUTED_TO, 'message:' . OrderPlacedForFlowGraph::class, 'handler:' . $subscriber::class . '::audit'],
        ], $graph);
        $emits = $graph->edgesOfKind(FlowEdgeKind::EMITS, from: $handlerId)[0];
        self::assertTrue($emits->isInferred());
        self::assertSame('recordThat', $emits->attributes['via']);
    }

    public function test_service_publishing_through_event_bus_parameter_and_sending_by_routing_key_through_injected_command_bus(): void
    {
        $service = new class () {
            public function __construct(private ?CommandBus $commandBus = null)
            {
            }

            #[CommandHandler(endpointId: 'order.place')]
            public function place(PlaceOrderForFlowGraph $command, EventBus $eventBus): void
            {
                $eventBus->publish(new OrderPlacedForFlowGraph($command->orderId));
                $this->commandBus->sendWithRouting('order.ship', ['orderId' => $command->orderId]);
            }

            #[CommandHandler('order.ship', endpointId: 'order.ship.handler')]
            public function ship(array $payload): void
            {
            }

            #[EventHandler(endpointId: 'order.placed.mail')]
            public function mail(OrderPlacedForFlowGraph $event): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $placeId = 'handler:' . $service::class . '::place';
        self::assertEdges([
            [FlowEdgeKind::EMITS, $placeId, 'message:' . OrderPlacedForFlowGraph::class],
            [FlowEdgeKind::EMITS, $placeId, 'message:order.ship'],
            [FlowEdgeKind::ROUTED_TO, 'message:order.ship', 'handler:' . $service::class . '::ship'],
        ], $graph);
        $viaByTarget = [];
        foreach ($graph->edgesOfKind(FlowEdgeKind::EMITS, from: $placeId) as $edge) {
            $viaByTarget[$edge->to] = $edge->attributes['via'];
        }
        self::assertSame(['message:' . OrderPlacedForFlowGraph::class => 'publish', 'message:order.ship' => 'sendWithRouting'], $viaByTarget);
    }

    public function test_event_published_by_a_handler_without_any_subscriber_appears_as_unhandled_event(): void
    {
        $service = new class () {
            #[CommandHandler(endpointId: 'order.place')]
            public function place(PlaceOrderForFlowGraph $command, EventBus $eventBus): void
            {
                $eventBus->publish(new OrderPlacedForFlowGraph($command->orderId));
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $event = $graph->node('message:' . OrderPlacedForFlowGraph::class);
        self::assertSame(FlowNodeKind::EVENT, $event->kind);
        self::assertFalse($event->attributes['handled']);
    }

    public function test_business_interface_sends_to_its_request_channel_and_a_handler_calling_it_is_linked(): void
    {
        $service = new class () {
            public function __construct(private ?OrderShippingForFlowGraph $shipping = null)
            {
            }

            #[CommandHandler(endpointId: 'order.place')]
            public function place(PlaceOrderForFlowGraph $command): void
            {
                $this->shipping->ship($command->orderId);
            }

            #[InternalHandler(inputChannelName: 'shipping.ship', endpointId: 'shipping.handler')]
            public function doShip(string $orderId): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class, OrderShippingForFlowGraph::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $gatewayId = 'gateway:' . OrderShippingForFlowGraph::class . '::ship';
        self::assertSame(FlowNodeKind::GATEWAY, $graph->node($gatewayId)->kind);
        self::assertEdges([
            [FlowEdgeKind::CALLS, 'handler:' . $service::class . '::place', $gatewayId],
            [FlowEdgeKind::SENDS_TO, $gatewayId, 'channel:shipping.ship'],
            [FlowEdgeKind::INPUT, 'channel:shipping.ship', 'handler:' . $service::class . '::doShip'],
        ], $graph);
    }

    public function test_router_is_linked_to_the_channels_its_source_names(): void
    {
        $service = new class () {
            #[Router(inputChannelName: 'order.route', endpointId: 'order.router')]
            public function route(string $orderId): string
            {
                return $orderId === 'vip' ? 'order.vip' : 'order.regular';
            }

            #[InternalHandler(inputChannelName: 'order.vip', endpointId: 'order.vip.handler')]
            public function vip(string $orderId): void
            {
            }

            #[InternalHandler(inputChannelName: 'order.regular', endpointId: 'order.regular.handler')]
            public function regular(string $orderId): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $routerId = 'handler:' . $service::class . '::route';
        self::assertSame(FlowNodeKind::ROUTER, $graph->node($routerId)->kind);
        self::assertEdges([
            [FlowEdgeKind::INPUT, 'channel:order.route', $routerId],
            [FlowEdgeKind::ROUTES_TO, $routerId, 'channel:order.vip'],
            [FlowEdgeKind::ROUTES_TO, $routerId, 'channel:order.regular'],
        ], $graph);
    }

    public function test_projection_handlers_belong_to_the_projection_node(): void
    {
        $projection = new #[Projection('orders_list'), FromStream('orders')] class () {
            #[EventHandler(endpointId: 'orders_list.placed')]
            public function whenPlaced(OrderPlacedForFlowGraph $event): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$projection::class], [$projection], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertSame(FlowNodeKind::PROJECTION, $graph->node('projection:orders_list')->kind);
        self::assertSame('projection:orders_list', $graph->node('handler:' . $projection::class . '::whenPlaced')->attributes['owner']);
        self::assertEdges([
            [FlowEdgeKind::ROUTED_TO, 'message:' . OrderPlacedForFlowGraph::class, 'handler:' . $projection::class . '::whenPlaced'],
        ], $graph);
    }

    public function test_distributed_handler_receives_its_message_from_the_distributed_bus(): void
    {
        $service = new class () {
            #[Distributed]
            #[CommandHandler('ticket.register', endpointId: 'ticket.register.handler')]
            public function register(array $payload): void
            {
            }

            #[Distributed]
            #[EventHandler('user.registered', endpointId: 'user.registered.handler')]
            public function whenUserRegistered(array $payload): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $busId = 'distributed:' . DistributedBus::class;
        self::assertSame(FlowNodeKind::DISTRIBUTED_BUS, $graph->node($busId)->kind);
        self::assertTrue($graph->node('message:ticket.register')->attributes['distributed']);
        self::assertTrue($graph->node('handler:' . $service::class . '::register')->attributes['distributed']);
        self::assertEdges([
            [FlowEdgeKind::DISTRIBUTES, $busId, 'message:ticket.register'],
            [FlowEdgeKind::DISTRIBUTES, $busId, 'message:user.registered'],
            [FlowEdgeKind::ROUTED_TO, 'message:ticket.register', 'handler:' . $service::class . '::register'],
        ], $graph);
    }

    public function test_handler_sending_through_the_distributed_bus_is_linked_to_the_remote_messages_and_the_bus(): void
    {
        $service = new class () {
            public function __construct(private ?DistributedBus $distributedBus = null)
            {
            }

            #[CommandHandler(endpointId: 'order.place')]
            public function place(PlaceOrderForFlowGraph $command): void
            {
                $this->distributedBus->convertAndSendCommand('billing', 'invoice.create', ['orderId' => $command->orderId]);
                $this->distributedBus->publishEvent('order.placed', '{}');
                $this->distributedBus->sendMessage('billing', 'billing.notifications', '{}');
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $busId = 'distributed:' . DistributedBus::class;
        $placeId = 'handler:' . $service::class . '::place';
        self::assertSame(FlowNodeKind::COMMAND, $graph->node('message:invoice.create')->kind);
        self::assertSame('billing', $graph->node('message:invoice.create')->attributes['targetService']);
        self::assertFalse($graph->node('message:invoice.create')->attributes['handled']);
        self::assertSame(FlowNodeKind::EVENT, $graph->node('message:order.placed')->kind);
        self::assertEdges([
            [FlowEdgeKind::EMITS, $placeId, 'message:invoice.create'],
            [FlowEdgeKind::SENDS_TO, 'message:invoice.create', $busId],
            [FlowEdgeKind::EMITS, $placeId, 'message:order.placed'],
            [FlowEdgeKind::SENDS_TO, 'message:order.placed', $busId],
            [FlowEdgeKind::SENDS_TO, $placeId, $busId],
        ], $graph);
        $direct = $graph->edgesOfKind(FlowEdgeKind::SENDS_TO, from: $placeId, to: $busId)[0];
        self::assertSame(['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'billing', 'targetChannel' => 'billing.notifications', 'withoutReply' => true], $direct->attributes);
        self::assertSame('convertAndSendCommand', $graph->edgesOfKind(FlowEdgeKind::EMITS, from: $placeId, to: 'message:invoice.create')[0]->attributes['via']);
    }

    public function test_service_map_wires_the_distributed_bus_to_its_command_and_event_channels(): void
    {
        $graph = EcotoneLite::bootstrapFlowTesting(
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel('billing_channel'),
                SimpleMessageChannelBuilder::createQueueChannel('events_channel'),
                DistributedServiceMap::initialize()
                    ->withCommandMapping('billing', 'billing_channel')
                    ->withEventMapping('events_channel', ['order.*']),
                ServiceConfiguration::createWithDefaults()->withServiceName('orders'),
            ],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        )->getMessageFlowGraph();

        $busId = 'distributed:' . DistributedBus::class;
        self::assertSame(['billing' => 'billing_channel'], $graph->node($busId)->attributes['commandMapping']);
        self::assertSame(['events_channel' => ['order.*']], $graph->node($busId)->attributes['eventSubscriptions']);
        self::assertEdges([
            [FlowEdgeKind::SENDS_TO, $busId, 'channel:billing_channel'],
            [FlowEdgeKind::SENDS_TO, $busId, 'channel:events_channel'],
        ], $graph);
        self::assertSame('billing', $graph->edgesOfKind(FlowEdgeKind::SENDS_TO, from: $busId, to: 'channel:billing_channel')[0]->attributes['targetService']);
        self::assertSame(['order.*'], $graph->edgesOfKind(FlowEdgeKind::SENDS_TO, from: $busId, to: 'channel:events_channel')[0]->attributes['subscriptionKeys']);
    }

    public function test_services_sharing_a_transport_channel_each_keep_their_route_from_the_distributed_bus(): void
    {
        $graph = EcotoneLite::bootstrapFlowTesting(
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel('shared_channel'),
                DistributedServiceMap::initialize()
                    ->withCommandMapping('billing', 'shared_channel')
                    ->withCommandMapping('shipping', 'shared_channel'),
                ServiceConfiguration::createWithDefaults()->withServiceName('orders'),
            ],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        )->getMessageFlowGraph();

        $routes = $graph->edgesOfKind(FlowEdgeKind::SENDS_TO, from: 'distributed:' . DistributedBus::class, to: 'channel:shared_channel');
        self::assertSame(['billing', 'shipping'], array_map(fn ($edge) => $edge->attributes['targetService'], $routes));
    }

    public function test_distributed_bus_publishes_events_only_to_channels_this_service_may_publish_to(): void
    {
        $graph = EcotoneLite::bootstrapFlowTesting(
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel('excluding_channel'),
                SimpleMessageChannelBuilder::createQueueChannel('including_others_channel'),
                SimpleMessageChannelBuilder::createQueueChannel('including_this_channel'),
                DistributedServiceMap::initialize()
                    ->withEventMapping('excluding_channel', ['order.*'], excludePublishingServices: ['orders'])
                    ->withEventMapping('including_others_channel', ['order.*'], includePublishingServices: ['billing'])
                    ->withEventMapping('including_this_channel', ['order.*'], includePublishingServices: ['orders']),
                ServiceConfiguration::createWithDefaults()->withServiceName('orders'),
            ],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        )->getMessageFlowGraph();

        self::assertSame(
            ['channel:including_this_channel'],
            array_map(fn ($edge) => $edge->to, $graph->edgesOfKind(FlowEdgeKind::SENDS_TO, from: 'distributed:' . DistributedBus::class)),
        );
    }

    public function test_channel_mapped_to_this_service_delivers_into_its_distributed_bus(): void
    {
        $graph = EcotoneLite::bootstrapFlowTesting(
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel('orders_channel'),
                SimpleMessageChannelBuilder::createQueueChannel('billing_channel'),
                DistributedServiceMap::initialize()
                    ->withCommandMapping('orders', 'orders_channel')
                    ->withCommandMapping('billing', 'billing_channel'),
                ServiceConfiguration::createWithDefaults()->withServiceName('orders'),
            ],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        )->getMessageFlowGraph();

        $busId = 'distributed:' . DistributedBus::class;
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::INPUT, 'channel:orders_channel', $busId));
        self::assertCount(0, $graph->edgesOfKind(FlowEdgeKind::INPUT, 'channel:billing_channel', $busId));
    }

    public function test_event_channel_this_service_subscribes_to_delivers_into_its_distributed_bus(): void
    {
        $billing = new class () {
            #[Distributed]
            #[EventHandler('user.billing_changed')]
            public function update(string $details): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting(
            [$billing::class],
            [$billing],
            [
                SimpleMessageChannelBuilder::createQueueChannel('billing_events'),
                SimpleMessageChannelBuilder::createQueueChannel('audit_events'),
                DistributedServiceMap::initialize()
                    ->withEventMapping(channelName: 'billing_events', subscriptionKeys: ['user.*'])
                    ->withEventMapping(channelName: 'audit_events', subscriptionKeys: ['order.*']),
                ServiceConfiguration::createWithDefaults()->withServiceName('billing'),
            ],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        )->getMessageFlowGraph();

        $busId = 'distributed:' . DistributedBus::class;
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::INPUT, 'channel:billing_events', $busId));
        self::assertCount(0, $graph->edgesOfKind(FlowEdgeKind::INPUT, 'channel:audit_events', $busId));
    }

    public function test_graph_serializes_to_array_and_back_without_loss(): void
    {
        $service = new class () {
            #[CommandHandler(endpointId: 'order.place', outputChannelName: 'order.placed.pipeline')]
            public function place(PlaceOrderForFlowGraph $command, EventBus $eventBus): string
            {
                $eventBus->publish(new OrderPlacedForFlowGraph($command->orderId));

                return $command->orderId;
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertEquals($graph, MessageFlowGraph::fromArray($graph->toArray()));
        self::assertSame($graph->toArray(), json_decode($graph->toJson(), true));
    }

    public function test_mermaid_rendering_groups_aggregate_handlers_and_marks_inferred_edges_as_dotted(): void
    {
        $aggregate = new #[Aggregate] class () {
            use WithEvents;

            #[Identifier]
            private string $orderId = '';

            #[CommandHandler]
            public static function place(PlaceOrderForFlowGraph $command): static
            {
                $order = new static();
                $order->orderId = $command->orderId;
                $order->recordThat(new OrderPlacedForFlowGraph($command->orderId));

                return $order;
            }
        };
        $subscriber = new class () {
            #[Asynchronous('orders')]
            #[EventHandler(endpointId: 'order.placed.mail')]
            public function mail(OrderPlacedForFlowGraph $event): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting(
            [$aggregate::class, $subscriber::class],
            [$subscriber],
            [SimpleMessageChannelBuilder::createQueueChannel('orders')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        )->getMessageFlowGraph();

        $mermaid = $graph->toMermaid();

        self::assertStringContainsString('flowchart LR', $mermaid);
        self::assertStringContainsString('subgraph', $mermaid);
        self::assertStringContainsString('Aggregate', $mermaid);
        self::assertStringContainsString('PlaceOrderForFlowGraph', $mermaid);
        self::assertStringContainsString('-.->|"emits (recordThat)"|', $mermaid);
        self::assertStringContainsString('==>|"async"|', $mermaid);
        self::assertStringContainsString('[("Channel<br/>orders")]', $mermaid);
    }

    public function test_mermaid_draws_an_asynchronous_handler_behind_its_channel_in_the_order_the_message_travels(): void
    {
        $notifications = new class () {
            #[Asynchronous('notifications')]
            #[EventHandler('order.placed', endpointId: 'notifications.orderPlaced')]
            public function whenPlaced(string $orderId): void
            {
            }
        };
        $invoicing = new class () {
            #[EventHandler('order.placed')]
            public function whenPlaced(string $orderId): void
            {
            }
        };
        $mermaid = EcotoneLite::bootstrapFlowTesting(
            [$notifications::class, $invoicing::class],
            [$notifications, $invoicing],
            [SimpleMessageChannelBuilder::createQueueChannel('notifications')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        )->getMessageFlowGraph()->toMermaid();

        $idOf = function (string $nodeLine) use ($mermaid): string {
            self::assertSame(1, preg_match('/^\s*(n\d+)' . preg_quote($nodeLine, '/') . '$/m', $mermaid), "Missing node {$nodeLine} in:\n{$mermaid}");
            preg_match('/^\s*(n\d+)' . preg_quote($nodeLine, '/') . '$/m', $mermaid, $match);

            return $match[1];
        };
        $event = $idOf('(["order.placed"])');
        $channel = $idOf('[("Channel<br/>notifications")]');
        $asynchronous = $idOf('["Event Handler<br/>' . $notifications::class . '::whenPlaced"]');
        $synchronous = $idOf('["Event Handler<br/>' . $invoicing::class . '::whenPlaced"]');

        self::assertStringContainsString("{$event} --> {$channel}\n", $mermaid);
        self::assertStringContainsString("{$channel} ==>|\"async\"| {$asynchronous}\n", $mermaid);
        self::assertStringContainsString("{$event} --> {$synchronous}\n", $mermaid);
        self::assertStringNotContainsString("{$event} --> {$asynchronous}\n", $mermaid);
    }

    public function test_dump_console_command_writes_mermaid_by_default_and_json_on_request(): void
    {
        $service = new class () {
            #[CommandHandler(endpointId: 'order.place')]
            public function place(PlaceOrderForFlowGraph $command): void
            {
            }
        };
        $ecotone = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE);

        $ecotone->runConsoleCommand('ecotone:flow:dump');
        self::assertStringContainsString('flowchart LR', $ecotone->getInMemoryConsoleWriter()->getWrittenContent());

        $ecotone->runConsoleCommand('ecotone:flow:dump', ['format' => 'json']);
        self::assertStringContainsString('"kind": "commandHandler"', $ecotone->getInMemoryConsoleWriter()->getWrittenContent());
        self::assertStringContainsString('"endpointId": "order.place"', $ecotone->getInMemoryConsoleWriter()->getWrittenContent());
    }

    public function test_dump_console_command_refuses_unknown_format_naming_the_available_ones(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('ecotone:flow:dump was asked for format `yaml`, which it cannot produce. Pass --format=mermaid for a Mermaid flowchart to paste into a diagram viewer, or --format=json for the graph as nodes and edges.');

        $ecotone->runConsoleCommand('ecotone:flow:dump', ['format' => 'yaml']);
    }

    /**
     * @param list<array{FlowEdgeKind, string, string}> $expected
     */
    private static function assertEdges(array $expected, MessageFlowGraph $graph): void
    {
        $actual = array_map(fn (FlowEdge $edge) => [$edge->kind, $edge->from, $edge->to], $graph->edges());
        foreach ($expected as $edge) {
            self::assertContains($edge, $actual, 'Missing edge ' . $edge[0]->value . ' from ' . $edge[1] . ' to ' . $edge[2] . ' in ' . $graph->toMermaid());
        }
    }
}

final class PlaceOrderForFlowGraph
{
    public function __construct(public readonly string $orderId)
    {
    }
}

final class FindOrderForFlowGraph
{
    public function __construct(public readonly string $orderId)
    {
    }
}

final class OrderPlacedForFlowGraph
{
    public function __construct(public readonly string $orderId)
    {
    }
}

interface OrderShippingForFlowGraph
{
    #[MessageGateway(requestChannel: 'shipping.ship')]
    public function ship(string $orderId): void;
}
