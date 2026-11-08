<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Flow;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Distributed;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\Reference;
use Ecotone\Api\ExtensionObject\MediaType;
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

    public function test_send_whose_routing_key_is_unresolved_keeps_its_edge_to_the_target_service_with_no_channel(): void
    {
        $service = new class () {
            #[CommandHandler('remote.command')]
            public function command(array $payload, DistributedBus $bus): void
            {
                $bus->sendCommand('ticketing', $payload['route'], '{}');
            }

            #[CommandHandler('remote.message')]
            public function message(array $payload, DistributedBus $bus): void
            {
                $bus->sendMessage('ticketing', $payload['route'], '{}', replyRoutingKey: 'tickets.result');
            }
        };
        $ecotone = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE);
        $graph = $ecotone->getMessageFlowGraph();

        $sendsOf = fn (string $method) => array_column($graph->edgesOfKind(FlowEdgeKind::SENDS_TO, 'handler:' . $service::class . '::' . $method, 'distributed:' . DistributedBus::class), 'attributes');
        self::assertSame([['inferred' => true, 'via' => 'sendCommand', 'targetService' => 'ticketing', 'targetChannel' => null]], $sendsOf('command'));
        self::assertSame([['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'ticketing', 'targetChannel' => null]], $sendsOf('message'));
        $ecotone->runConsoleCommand('ecotone:flow:dump');
        self::assertSame(2, substr_count($ecotone->getInMemoryConsoleWriter()->getWrittenContent(), ' to ticketing"|'));
    }

    public function test_distributed_command_handler_returning_a_value_replies_through_its_bus_and_a_void_one_does_not(): void
    {
        $service = new class () {
            #[Distributed]
            #[CommandHandler('tickets.hold')]
            public function hold(array $payload): array
            {
                return ['outcome' => 'held'];
            }

            #[Distributed]
            #[CommandHandler('tickets.release')]
            public function release(array $payload): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $bus = 'distributed:' . DistributedBus::class;
        self::assertSame(
            [['via' => 'replyRoutingKey']],
            array_column($graph->edgesOfKind(FlowEdgeKind::SENDS_TO, 'handler:' . $service::class . '::hold', $bus), 'attributes'),
        );
        self::assertSame([], $graph->edgesOfKind(FlowEdgeKind::SENDS_TO, 'handler:' . $service::class . '::release', $bus));
    }

    public function test_only_a_declared_return_type_other_than_void_draws_a_reply(): void
    {
        $service = new class () {
            #[Distributed]
            #[CommandHandler('tickets.hold')]
            public function hold(array $payload): array
            {
                return ['outcome' => 'held'];
            }

            #[Distributed]
            #[CommandHandler('tickets.note')]
            public function note(array $payload)
            {
            }

            #[Distributed]
            #[CommandHandler('tickets.drop')]
            public function drop(array $payload): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $repliesOf = fn (string $handler) => array_column($graph->edgesOfKind(FlowEdgeKind::SENDS_TO, 'handler:' . $service::class . '::' . $handler, 'distributed:' . DistributedBus::class), 'attributes');
        self::assertSame([['via' => 'replyRoutingKey']], $repliesOf('hold'));
        self::assertSame([], $repliesOf('note'));
        self::assertSame([], $repliesOf('drop'));
    }

    public function test_aggregate_factory_draws_a_reply_because_it_replies_with_the_new_identifier(): void
    {
        $aggregate = new #[Aggregate] class ('ticket-1') {
            public function __construct(#[Identifier] public string $ticketId)
            {
            }

            #[Distributed]
            #[CommandHandler('tickets.open')]
            public static function open(array $payload): self
            {
                return new self($payload['ticketId']);
            }

            #[Distributed]
            #[CommandHandler('tickets.reopen')]
            public static function reopen(array $payload): static
            {
                return new static($payload['ticketId']);
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$aggregate::class], [], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        foreach (['open', 'reopen'] as $factory) {
            self::assertSame(
                [['via' => 'replyRoutingKey']],
                array_column($graph->edgesOfKind(FlowEdgeKind::SENDS_TO, 'handler:' . $aggregate::class . '::' . $factory, 'distributed:' . DistributedBus::class), 'attributes'),
            );
        }
    }

    public function test_distributed_command_handler_that_replies_and_sends_an_unresolved_message_shows_both(): void
    {
        $service = new class () {
            #[Distributed]
            #[CommandHandler('tickets.hold')]
            public function hold(array $payload, DistributedBus $distributedBus): array
            {
                $distributedBus->sendMessage('ticketing', $payload['route'], '{}');

                return ['outcome' => 'held'];
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertSame(
            [['via' => 'replyRoutingKey'], ['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'ticketing', 'targetChannel' => null]],
            array_column($graph->edgesOfKind(FlowEdgeKind::SENDS_TO, 'handler:' . $service::class . '::hold', 'distributed:' . DistributedBus::class), 'attributes'),
        );
    }

    public function test_send_through_a_helper_whose_routing_key_is_a_parameter_records_the_route_of_each_call_site(): void
    {
        $service = new class () {
            public const RELEASE = 'tickets.release';

            #[EventHandler('seat.requested')]
            public function holdRequestedSeat(array $event, DistributedBus $distributedBus): void
            {
                $this->send($distributedBus, 'tickets.hold', ['seatId' => $event['seatId'], 'nested' => [1, 2]], replyRoutingKey: 'tickets.hold.result');
            }

            #[EventHandler('seat.released')]
            public function releaseSeat(array $event, DistributedBus $distributedBus): void
            {
                $this->sendRelease($distributedBus, $event['seatId']);
            }

            #[EventHandler('order.paid')]
            public function confirmPaidSeats(array $event, DistributedBus $distributedBus): void
            {
                $this->send(distributedBus: $distributedBus, routingKey: TicketRoute::CONFIRM->value, payload: $event, replyRoutingKey: 'tickets.confirm.result');
                $this->send($distributedBus, 'tickets.audit', $event, null);
            }

            private function sendRelease(DistributedBus $distributedBus, string $seatId): void
            {
                $this->send($distributedBus, self::RELEASE, ['seatId' => $seatId], replyRoutingKey: null);
            }

            private function send(DistributedBus $distributedBus, string $routingKey, array $payload, ?string $replyRoutingKey): void
            {
                $distributedBus->sendMessage(
                    targetServiceName: 'ticketing',
                    routingKey: $routingKey,
                    payload: json_encode($payload, JSON_THROW_ON_ERROR),
                    sourceMediaType: 'application/json',
                    metadata: ['seatId' => $payload['seatId'] ?? null],
                    replyRoutingKey: $replyRoutingKey,
                );
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $bus = 'distributed:' . DistributedBus::class;
        $handler = fn (string $method) => 'handler:' . $service::class . '::' . $method;
        self::assertSame(
            [['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'ticketing', 'targetChannel' => 'tickets.hold', 'replyChannel' => 'tickets.hold.result']],
            array_column($graph->edgesOfKind(FlowEdgeKind::SENDS_TO, $handler('holdRequestedSeat'), $bus), 'attributes'),
        );
        self::assertSame(
            [['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'ticketing', 'targetChannel' => 'tickets.release', 'withoutReply' => true]],
            array_column($graph->edgesOfKind(FlowEdgeKind::SENDS_TO, $handler('releaseSeat'), $bus), 'attributes'),
        );
        self::assertSame(
            [
                ['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'ticketing', 'targetChannel' => 'tickets.confirm', 'replyChannel' => 'tickets.confirm.result'],
                ['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'ticketing', 'targetChannel' => 'tickets.audit', 'withoutReply' => true],
            ],
            array_column($graph->edgesOfKind(FlowEdgeKind::SENDS_TO, $handler('confirmPaidSeats'), $bus), 'attributes'),
        );
    }

    public function test_every_route_the_storefront_sends_to_ticketing_through_its_helpers_is_inferred(): void
    {
        $service = new class () {
            #[EventHandler('seat.requested', endpointId: 'hold_requested_seat')]
            public function holdRequestedSeat(array $event, #[Reference] DistributedBus $distributedBus): void
            {
                $this->send(distributedBus: $distributedBus, routingKey: 'tickets.hold', orderId: $event['orderId'], concertId: $event['concertId'], payload: [
                    'orderId' => $event['orderId'],
                    'seatId' => $event['seatId'],
                ], replyRoutingKey: 'tickets.hold.result');
            }

            #[EventHandler('seat.released', endpointId: 'release_seat')]
            public function releaseSeat(array $event, #[Reference] DistributedBus $distributedBus): void
            {
                $this->sendRelease(distributedBus: $distributedBus, orderId: $event['orderId'], concertId: $event['concertId'], seatId: $event['seatId']);
            }

            #[EventHandler('order.failed', endpointId: 'release_seats_of_failed_order')]
            public function releaseSeatsOfFailedOrder(array $event, #[Reference] DistributedBus $distributedBus): void
            {
                foreach ($event['holdIdsOfHeldSeats'] as $seatId => $holdId) {
                    $this->sendRelease(distributedBus: $distributedBus, orderId: $event['orderId'], concertId: $event['concertId'], seatId: $seatId);
                }
            }

            #[EventHandler('order.paid', endpointId: 'confirm_paid_seats')]
            public function confirmPaidSeats(array $event, #[Reference] DistributedBus $distributedBus): void
            {
                $this->send(distributedBus: $distributedBus, routingKey: 'tickets.confirm', orderId: $event['orderId'], concertId: $event['concertId'], payload: [
                    'orderId' => $event['orderId'],
                    'seatIds' => $event['seatIds'],
                ], replyRoutingKey: 'tickets.confirm.result');
            }

            #[EventHandler('demo.ping.requested', endpointId: 'demo_ping')]
            public function ping(array $event, #[Reference] DistributedBus $distributedBus): void
            {
                $distributedBus->sendMessage('ticketing', 'demo.ping', 'storefront', metadata: ['pingId' => $event['pingId']], replyRoutingKey: 'demo.pong');
            }

            #[EventHandler('seat.audited', endpointId: 'audit_seat')]
            public function auditSeat(array $event, #[Reference] DistributedBus $distributedBus): void
            {
                $this->sendWithDefaultReply($distributedBus, 'tickets.audit');
            }

            private function sendRelease(DistributedBus $distributedBus, string $orderId, string $concertId, string $seatId): void
            {
                $this->send(distributedBus: $distributedBus, routingKey: 'tickets.release', orderId: $orderId, concertId: $concertId, payload: [
                    'orderId' => $orderId,
                    'seatId' => $seatId,
                ], replyRoutingKey: null);
            }

            private function send(DistributedBus $distributedBus, string $routingKey, string $orderId, string $concertId, array $payload, ?string $replyRoutingKey): void
            {
                $distributedBus->sendMessage(
                    targetServiceName: 'ticketing',
                    routingKey: $routingKey,
                    payload: json_encode($payload, JSON_THROW_ON_ERROR),
                    sourceMediaType: MediaType::APPLICATION_JSON,
                    metadata: ['concertId' => $concertId, 'orderId' => $orderId],
                    replyRoutingKey: $replyRoutingKey,
                );
            }

            private function sendWithDefaultReply(DistributedBus $distributedBus, string $routingKey, ?string $replyRoutingKey = null): void
            {
                $distributedBus->sendMessage('ticketing', $routingKey, '{}', replyRoutingKey: $replyRoutingKey);
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $sendsOf = fn (string $method) => array_column($graph->edgesOfKind(FlowEdgeKind::SENDS_TO, 'handler:' . $service::class . '::' . $method, 'distributed:' . DistributedBus::class), 'attributes');
        self::assertSame([['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'ticketing', 'targetChannel' => 'tickets.hold', 'replyChannel' => 'tickets.hold.result']], $sendsOf('holdRequestedSeat'));
        self::assertSame([['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'ticketing', 'targetChannel' => 'tickets.release', 'withoutReply' => true]], $sendsOf('releaseSeat'));
        self::assertSame([['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'ticketing', 'targetChannel' => 'tickets.release', 'withoutReply' => true]], $sendsOf('releaseSeatsOfFailedOrder'));
        self::assertSame([['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'ticketing', 'targetChannel' => 'tickets.confirm', 'replyChannel' => 'tickets.confirm.result']], $sendsOf('confirmPaidSeats'));
        self::assertSame([['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'ticketing', 'targetChannel' => 'demo.ping', 'replyChannel' => 'demo.pong']], $sendsOf('ping'));
        self::assertSame([['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'ticketing', 'targetChannel' => 'tickets.audit', 'withoutReply' => true]], $sendsOf('auditSeat'));
    }

    public function test_routing_key_computed_at_runtime_stays_unresolved_instead_of_guessed(): void
    {
        $service = new class () {
            #[EventHandler('seat.requested')]
            public function concatenated(array $event, DistributedBus $distributedBus): void
            {
                $this->send($distributedBus, 'tickets.' . $event['action']);
            }

            #[EventHandler('seat.released')]
            public function readFromThePayload(array $event, DistributedBus $distributedBus): void
            {
                $this->send($distributedBus, $event['route']);
            }

            #[EventHandler('order.paid')]
            public function reassignedInTheHelper(array $event, DistributedBus $distributedBus): void
            {
                $this->sendPrefixed($distributedBus, 'hold');
            }

            #[EventHandler('order.failed')]
            public function loopVariable(array $event, DistributedBus $distributedBus): void
            {
                foreach ($event['routes'] as $route) {
                    $this->send($distributedBus, $route);
                }
            }

            private function sendPrefixed(DistributedBus $distributedBus, string $routingKey): void
            {
                $routingKey = 'tickets.' . $routingKey;
                $distributedBus->sendMessage('ticketing', $routingKey, '{}');
            }

            private function send(DistributedBus $distributedBus, string $routingKey): void
            {
                $distributedBus->sendMessage('ticketing', $routingKey, '{}');
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        foreach (['concatenated', 'readFromThePayload', 'reassignedInTheHelper', 'loopVariable'] as $method) {
            self::assertSame(
                [['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'ticketing', 'targetChannel' => null]],
                array_column($graph->edgesOfKind(FlowEdgeKind::SENDS_TO, 'handler:' . $service::class . '::' . $method, 'distributed:' . DistributedBus::class), 'attributes'),
            );
        }
    }

    public function test_routing_key_handed_by_reference_to_a_followed_helper_is_unresolved_from_there_on(): void
    {
        $service = new class () {
            #[EventHandler('seat.requested')]
            public function holdRequestedSeat(array $event, DistributedBus $distributedBus): void
            {
                $this->send($distributedBus, 'tickets.hold');
            }

            #[EventHandler('seat.released')]
            public function releaseSeat(array $event, DistributedBus $distributedBus): void
            {
                $this->sendDescribed($distributedBus, 'tickets.release');
            }

            private function send(DistributedBus $distributedBus, string $routingKey): void
            {
                $this->normalize($routingKey);
                $distributedBus->sendMessage('ticketing', $routingKey, '{}');
            }

            private function sendDescribed(DistributedBus $distributedBus, string $routingKey): void
            {
                $this->describe($routingKey);
                $distributedBus->sendMessage('ticketing', $routingKey, '{}');
            }

            private function normalize(string &$routingKey): void
            {
                $routingKey = 'tickets.v2.' . $routingKey;
            }

            private function describe(string $routingKey): string
            {
                return 'route ' . $routingKey;
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $sendsOf = fn (string $method) => array_column($graph->edgesOfKind(FlowEdgeKind::SENDS_TO, 'handler:' . $service::class . '::' . $method, 'distributed:' . DistributedBus::class), 'attributes');
        self::assertSame([['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'ticketing', 'targetChannel' => null]], $sendsOf('holdRequestedSeat'));
        self::assertSame([['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'ticketing', 'targetChannel' => 'tickets.release', 'withoutReply' => true]], $sendsOf('releaseSeat'));
    }

    public function test_helper_default_routing_key_applies_only_when_the_call_site_omits_the_argument(): void
    {
        $service = new class () {
            #[EventHandler('seat.requested')]
            public function omitted(array $event, DistributedBus $distributedBus): void
            {
                $this->send($distributedBus);
            }

            #[EventHandler('seat.released')]
            public function readFromThePayload(array $event, DistributedBus $distributedBus): void
            {
                $this->send($distributedBus, $event['route']);
            }

            #[EventHandler('order.paid')]
            public function namedAndConcatenated(array $event, DistributedBus $distributedBus): void
            {
                $this->send(distributedBus: $distributedBus, routingKey: 'tickets.' . $event['action']);
            }

            private function send(DistributedBus $distributedBus, string $routingKey = 'tickets.hold'): void
            {
                $distributedBus->sendMessage('ticketing', $routingKey, '{}');
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $sendsOf = fn (string $method) => array_column($graph->edgesOfKind(FlowEdgeKind::SENDS_TO, 'handler:' . $service::class . '::' . $method, 'distributed:' . DistributedBus::class), 'attributes');
        self::assertSame([['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'ticketing', 'targetChannel' => 'tickets.hold', 'withoutReply' => true]], $sendsOf('omitted'));
        self::assertSame([['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'ticketing', 'targetChannel' => null]], $sendsOf('readFromThePayload'));
        self::assertSame([['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'ticketing', 'targetChannel' => null]], $sendsOf('namedAndConcatenated'));
    }

    public function test_helper_default_does_not_fill_a_parameter_the_call_site_spreads_into(): void
    {
        $service = new class () {
            #[EventHandler('seat.requested')]
            public function spread(array $event, DistributedBus $distributedBus): void
            {
                $this->send($distributedBus, ...$event['arguments']);
            }

            private function send(DistributedBus $distributedBus, string $routingKey = 'tickets.hold'): void
            {
                $distributedBus->sendMessage('ticketing', $routingKey, '{}');
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertSame(
            [['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'ticketing', 'targetChannel' => null]],
            array_column($graph->edgesOfKind(FlowEdgeKind::SENDS_TO, 'handler:' . $service::class . '::spread', 'distributed:' . DistributedBus::class), 'attributes'),
        );
    }

    public function test_routing_key_destructured_in_the_helper_stays_unresolved(): void
    {
        $service = new class () {
            #[EventHandler('seat.requested')]
            public function shortArray(array $event, DistributedBus $distributedBus): void
            {
                $this->sendShortArray($distributedBus, 'tickets.hold');
            }

            #[EventHandler('seat.released')]
            public function keyedShortArray(array $event, DistributedBus $distributedBus): void
            {
                $this->sendKeyed($distributedBus, 'tickets.hold');
            }

            #[EventHandler('order.paid')]
            public function keyedInForeach(array $event, DistributedBus $distributedBus): void
            {
                $this->sendForeach($distributedBus, 'tickets.hold', $event['routes']);
            }

            #[EventHandler('order.failed')]
            public function keyedInForeachWithItsKey(array $event, DistributedBus $distributedBus): void
            {
                $this->sendForeachWithKey($distributedBus, 'tickets.hold', $event['routes']);
            }

            private function sendShortArray(DistributedBus $distributedBus, string $routingKey): void
            {
                [$version, $routingKey] = explode('|', 'v2|' . $routingKey);
                $distributedBus->sendMessage('ticketing', $routingKey, $version);
            }

            private function sendKeyed(DistributedBus $distributedBus, string $routingKey): void
            {
                ['route' => $routingKey] = ['route' => $routingKey . '.v2'];
                $distributedBus->sendMessage('ticketing', $routingKey, '{}');
            }

            private function sendForeach(DistributedBus $distributedBus, string $routingKey, array $routes): void
            {
                foreach ($routes as [$routingKey]) {
                    $distributedBus->sendMessage('ticketing', $routingKey, '{}');
                }
            }

            private function sendForeachWithKey(DistributedBus $distributedBus, string $routingKey, array $routes): void
            {
                foreach ($routes as $index => [$routingKey]) {
                    $distributedBus->sendMessage('ticketing', $routingKey, (string) $index);
                }
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        foreach (['shortArray', 'keyedShortArray', 'keyedInForeach', 'keyedInForeachWithItsKey'] as $method) {
            self::assertSame(
                [['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'ticketing', 'targetChannel' => null]],
                array_column($graph->edgesOfKind(FlowEdgeKind::SENDS_TO, 'handler:' . $service::class . '::' . $method, 'distributed:' . DistributedBus::class), 'attributes'),
            );
        }
    }

    public function test_helper_bus_bound_to_an_unresolved_argument_is_not_credited_to_a_handler_parameter_of_the_same_name(): void
    {
        $service = new class () {
            private ?AuditDistributedBus $auditBus = null;

            #[EventHandler('seat.requested')]
            public function holdRequestedSeat(array $event, DistributedBus $distributedBus): void
            {
                $this->audit($this->auditBus);
            }

            private function audit(DistributedBus $distributedBus): void
            {
                $distributedBus->sendMessage('audit', 'audit.seat', '{}');
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertSame([], $graph->edgesOfKind(FlowEdgeKind::SENDS_TO, 'handler:' . $service::class . '::holdRequestedSeat'));
    }

    public function test_routing_key_the_helper_may_no_longer_hold_stays_unresolved(): void
    {
        $service = new class () {
            private ?object $routes = null;

            #[EventHandler('seat.requested')]
            public function shadowedByAnArrowFunction(array $event, DistributedBus $distributedBus): void
            {
                $this->sendEachArrow($distributedBus, 'tickets.hold', $event['routes']);
            }

            #[EventHandler('seat.released')]
            public function shadowedByAClosure(array $event, DistributedBus $distributedBus): void
            {
                $this->sendEachClosure($distributedBus, 'tickets.hold', $event['routes']);
            }

            #[EventHandler('order.paid')]
            public function passedToAFunction(array $event, DistributedBus $distributedBus): void
            {
                $this->sendScanned($distributedBus, 'tickets.hold', $event['subject']);
            }

            #[EventHandler('order.failed')]
            public function passedToAMethod(array $event, DistributedBus $distributedBus): void
            {
                $this->sendRewritten($distributedBus, 'tickets.hold');
            }

            #[EventHandler('order.cancelled')]
            public function extracted(array $event, DistributedBus $distributedBus): void
            {
                $this->sendExtracted($distributedBus, 'tickets.hold', $event);
            }

            private function sendEachArrow(DistributedBus $distributedBus, string $routingKey, array $routes): void
            {
                array_map(fn (string $routingKey) => $distributedBus->sendMessage('ticketing', $routingKey, '{}'), $routes);
            }

            private function sendEachClosure(DistributedBus $distributedBus, string $routingKey, array $routes): void
            {
                foreach ($routes as $route) {
                    (function (string $routingKey) use ($distributedBus): void {
                        $distributedBus->sendMessage('ticketing', $routingKey, '{}');
                    })($route);
                }
            }

            private function sendScanned(DistributedBus $distributedBus, string $routingKey, string $subject): void
            {
                sscanf($subject, 'route:%s', $routingKey);
                $distributedBus->sendMessage('ticketing', $routingKey, '{}');
            }

            private function sendRewritten(DistributedBus $distributedBus, string $routingKey): void
            {
                $this->routes?->rewrite($routingKey);
                $distributedBus->sendMessage('ticketing', $routingKey, '{}');
            }

            private function sendExtracted(DistributedBus $distributedBus, string $routingKey, array $options): void
            {
                extract($options);
                $distributedBus->sendMessage('ticketing', $routingKey, '{}');
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        foreach (['shadowedByAnArrowFunction', 'shadowedByAClosure', 'passedToAFunction', 'passedToAMethod', 'extracted'] as $method) {
            self::assertSame(
                [['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'ticketing', 'targetChannel' => null]],
                array_column($graph->edgesOfKind(FlowEdgeKind::SENDS_TO, 'handler:' . $service::class . '::' . $method, 'distributed:' . DistributedBus::class), 'attributes'),
            );
        }
    }

    public function test_routing_key_stays_resolved_outside_a_shadowing_closure_and_before_a_call_that_may_reassign_it(): void
    {
        $service = new class () {
            private ?object $routes = null;

            #[EventHandler('seat.requested')]
            public function outsideTheClosure(array $event, DistributedBus $distributedBus): void
            {
                $this->sendThenMap($distributedBus, 'tickets.hold', $event['routes']);
            }

            #[EventHandler('seat.released')]
            public function beforeTheCall(array $event, DistributedBus $distributedBus): void
            {
                $this->sendThenRewrite($distributedBus, 'tickets.hold');
            }

            private function sendThenMap(DistributedBus $distributedBus, string $routingKey, array $routes): void
            {
                $distributedBus->sendMessage('ticketing', $routingKey, '{}');
                array_map(fn (string $routingKey) => strtoupper($routingKey), $routes);
            }

            private function sendThenRewrite(DistributedBus $distributedBus, string $routingKey): void
            {
                $distributedBus->sendMessage('ticketing', $routingKey, '{}');
                $this->routes?->rewrite($routingKey);
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        foreach (['outsideTheClosure', 'beforeTheCall'] as $method) {
            self::assertSame(
                [['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'ticketing', 'targetChannel' => 'tickets.hold', 'withoutReply' => true]],
                array_column($graph->edgesOfKind(FlowEdgeKind::SENDS_TO, 'handler:' . $service::class . '::' . $method, 'distributed:' . DistributedBus::class), 'attributes'),
            );
        }
    }

    public function test_helpers_calling_each_other_resolve_the_key_once_and_stop(): void
    {
        $service = new class () {
            #[EventHandler('seat.requested')]
            public function holdRequestedSeat(array $event, DistributedBus $distributedBus): void
            {
                $this->first($distributedBus, 'tickets.hold', 3);
            }

            private function first(DistributedBus $distributedBus, string $routingKey, int $attempts): void
            {
                if ($attempts > 0) {
                    $this->second($distributedBus, $routingKey, $attempts - 1);
                }
            }

            private function second(DistributedBus $distributedBus, string $routingKey, int $attempts): void
            {
                $distributedBus->sendMessage('ticketing', $routingKey, '{}');
                $this->first($distributedBus, $routingKey, $attempts);
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertSame(
            [['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'ticketing', 'targetChannel' => 'tickets.hold', 'withoutReply' => true]],
            array_column($graph->edgesOfKind(FlowEdgeKind::SENDS_TO, 'handler:' . $service::class . '::holdRequestedSeat', 'distributed:' . DistributedBus::class), 'attributes'),
        );
    }

    public function test_routing_key_and_reply_key_read_from_a_method_of_a_typed_parameter_returning_a_literal_a_constant_or_an_enum_value_are_resolved(): void
    {
        $service = new class () {
            #[EventHandler]
            public function holdRequestedSeat(SeatHoldRequested $event, DistributedBus $distributedBus): void
            {
                $distributedBus->sendMessage('ticketing', $event->routingKey(), '{}', replyRoutingKey: $event->replyRoutingKey());
            }

            #[EventHandler]
            public function confirmPaidSeats(SeatsPaid $event, DistributedBus $distributedBus): void
            {
                $this->send(distributedBus: $distributedBus, routingKey: $event->routingKey(), replyRoutingKey: $event->replyRoutingKey());
            }

            private function send(DistributedBus $distributedBus, string $routingKey, ?string $replyRoutingKey): void
            {
                $distributedBus->sendMessage(targetServiceName: 'ticketing', routingKey: $routingKey, payload: '{}', replyRoutingKey: $replyRoutingKey);
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $sendsOf = fn (string $method) => array_column($graph->edgesOfKind(FlowEdgeKind::SENDS_TO, 'handler:' . $service::class . '::' . $method, 'distributed:' . DistributedBus::class), 'attributes');
        self::assertSame([['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'ticketing', 'targetChannel' => 'tickets.hold', 'replyChannel' => 'tickets.hold.result']], $sendsOf('holdRequestedSeat'));
        self::assertSame([['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'ticketing', 'targetChannel' => 'tickets.confirm', 'replyChannel' => 'tickets.confirm.result']], $sendsOf('confirmPaidSeats'));
    }

    public function test_routing_key_read_as_a_constant_of_a_typed_parameter_class_is_resolved(): void
    {
        $service = new class () {
            #[EventHandler]
            public function holdRequestedSeat(SeatHoldRequested $event, DistributedBus $distributedBus): void
            {
                $distributedBus->sendMessage('ticketing', $event::ROUTING_KEY, '{}', replyRoutingKey: $event::REPLY_ROUTING_KEY);
            }

            #[EventHandler]
            public function confirmPaidSeats(SeatsPaid $event, DistributedBus $distributedBus): void
            {
                $distributedBus->sendMessage('ticketing', $event::ROUTE->value, '{}');
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $sendsOf = fn (string $method) => array_column($graph->edgesOfKind(FlowEdgeKind::SENDS_TO, 'handler:' . $service::class . '::' . $method, 'distributed:' . DistributedBus::class), 'attributes');
        self::assertSame([['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'ticketing', 'targetChannel' => 'tickets.hold', 'replyChannel' => 'tickets.hold.result']], $sendsOf('holdRequestedSeat'));
        self::assertSame([['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'ticketing', 'targetChannel' => 'tickets.confirm', 'withoutReply' => true]], $sendsOf('confirmPaidSeats'));
    }

    public function test_routing_key_read_from_a_method_of_a_typed_property_is_resolved(): void
    {
        $service = new class (new TicketingRoutes()) {
            public function __construct(private TicketingRoutes $routes)
            {
            }

            #[EventHandler('seat.requested')]
            public function holdRequestedSeat(array $event, DistributedBus $distributedBus): void
            {
                $distributedBus->sendMessage('ticketing', $this->routes->hold(), '{}');
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertSame(
            [['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'ticketing', 'targetChannel' => 'tickets.hold', 'withoutReply' => true]],
            array_column($graph->edgesOfKind(FlowEdgeKind::SENDS_TO, 'handler:' . $service::class . '::holdRequestedSeat', 'distributed:' . DistributedBus::class), 'attributes'),
        );
    }

    public function test_routing_key_read_from_a_method_returning_anything_but_a_literal_or_from_an_overridable_member_stays_unresolved(): void
    {
        $service = new class () {
            #[EventHandler]
            public function fromAProperty(SeatsPaid $event, DistributedBus $distributedBus): void
            {
                $distributedBus->sendMessage('ticketing', $event->storedRoutingKey(), '{}');
            }

            #[EventHandler]
            public function fromAMatch(SeatsPaid $event, DistributedBus $distributedBus): void
            {
                $distributedBus->sendMessage('ticketing', $event->matchedRoutingKey(), '{}');
            }

            #[EventHandler]
            public function fromAConcatenation(SeatsPaid $event, DistributedBus $distributedBus): void
            {
                $distributedBus->sendMessage('ticketing', $event->prefixedRoutingKey(), '{}');
            }

            #[EventHandler]
            public function fromAnOverridableMethod(SeatReleaseRequested $event, DistributedBus $distributedBus): void
            {
                $distributedBus->sendMessage('ticketing', $event->routingKey(), '{}');
            }

            #[EventHandler]
            public function fromAnOverridableConstant(SeatReleaseRequested $event, DistributedBus $distributedBus): void
            {
                $distributedBus->sendMessage('ticketing', $event::ROUTING_KEY, '{}');
            }

            #[EventHandler]
            public function fromAnInterfaceMethod(RoutedTicketRequest $event, DistributedBus $distributedBus): void
            {
                $distributedBus->sendMessage('ticketing', $event->routingKey(), '{}');
            }

            #[EventHandler]
            public function fromAReassignedParameter(SeatHoldRequested $event, DistributedBus $distributedBus): void
            {
                $event = $event->retried();
                $distributedBus->sendMessage('ticketing', $event->routingKey(), '{}');
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        foreach (['fromAProperty', 'fromAMatch', 'fromAConcatenation', 'fromAnOverridableMethod', 'fromAnOverridableConstant', 'fromAnInterfaceMethod', 'fromAReassignedParameter'] as $method) {
            self::assertSame(
                [['inferred' => true, 'via' => 'sendMessage', 'targetService' => 'ticketing', 'targetChannel' => null]],
                array_column($graph->edgesOfKind(FlowEdgeKind::SENDS_TO, 'handler:' . $service::class . '::' . $method, 'distributed:' . DistributedBus::class), 'attributes'),
            );
        }
    }
}

interface RoutedTicketRequest
{
    public function routingKey(): string;
}

final class SeatHoldRequested implements RoutedTicketRequest
{
    public const ROUTING_KEY = 'tickets.hold';
    public const REPLY_ROUTING_KEY = 'tickets.hold.result';

    public function routingKey(): string
    {
        return 'tickets.hold';
    }

    public function replyRoutingKey(): string
    {
        return self::REPLY_ROUTING_KEY;
    }

    public function retried(): self
    {
        return $this;
    }
}

final class SeatsPaid
{
    public const ROUTE = TicketRoute::CONFIRM;

    public function __construct(private string $routingKey = 'tickets.confirm', private string $action = 'confirm')
    {
    }

    public function routingKey(): string
    {
        return TicketRoute::CONFIRM->value;
    }

    public function replyRoutingKey(): string
    {
        return 'tickets.confirm.result';
    }

    public function storedRoutingKey(): string
    {
        return $this->routingKey;
    }

    public function matchedRoutingKey(): string
    {
        return match ($this->action) {
            'confirm' => 'tickets.confirm',
            default => 'tickets.release',
        };
    }

    public function prefixedRoutingKey(): string
    {
        return 'tickets.' . 'confirm';
    }
}

class SeatReleaseRequested
{
    public const ROUTING_KEY = 'tickets.release';

    public function routingKey(): string
    {
        return 'tickets.release';
    }
}

final class TicketingRoutes
{
    public function hold(): string
    {
        return 'tickets.hold';
    }
}

final class LocalEventForDistribution
{
}

interface AuditDistributedBus extends DistributedBus
{
}

enum TicketRoute: string
{
    case CONFIRM = 'tickets.confirm';
}
