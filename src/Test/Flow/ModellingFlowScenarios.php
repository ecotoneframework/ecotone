<?php

declare(strict_types=1);

namespace Ecotone\Test\Flow;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\Saga;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Modelling\WithAggregateVersioning;
use Ecotone\Api\Modelling\WithEvents;
use Ecotone\Api\Projecting\FromAggregateStream;
use Ecotone\Api\Projecting\Projection;
use Ecotone\Api\Tracing\SpanAttribute;
use Ecotone\Api\Tracing\SpanKind;
use Ecotone\Api\Tracing\SpanStatus;
use Ecotone\Test\Flow\Fixture\FlowOrder;
use Ecotone\Test\Flow\Fixture\FlowOrderPlaced;
use Ecotone\Test\Flow\Fixture\FlowPaymentReceived;

/**
 * licence Apache-2.0
 */
final class ModellingFlowScenarios
{
    /**
     * @return list<FlowScenario>
     */
    public static function all(): array
    {
        return [
            self::stateStoredAggregateRecordingAnEvent(),
            self::eventSourcedAggregateReturningAnEvent(),
            self::sagaStartedByAnEventSendingACommand(),
            self::sagaContinuedByASecondEventMappedToIt(),
            self::eventForAnUnknownSagaIsDropped(),
            self::eventSourcedAggregateFeedingAProjection(),
        ];
    }

    private static function stateStoredAggregateRecordingAnEvent(): FlowScenario
    {
        $order = new #[Aggregate] class () {
            use WithEvents;

            #[Identifier]
            public string $orderId = '';

            #[CommandHandler('order.create', endpointId: 'order.creating')]
            public static function create(array $command): self
            {
                $order = new self();
                $order->orderId = $command['orderId'];
                $order->recordThat(new FlowOrderPlaced($command['orderId']));

                return $order;
            }
        };
        $subscriber = self::orderPlacedSubscriber();

        return new FlowScenario('state-stored aggregate recording an event', [$order::class, $subscriber::class, FlowOrderPlaced::class], [$subscriber], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('order.create', ['orderId' => '1'], metadata: $metadata))
                ->expect(
                    ExpectedSpan::named('send command')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'order.create'])->containing(
                        ExpectedSpan::named('process class@anonymous::create')->withAttributes([SpanAttribute::ENDPOINT_ID => 'order.creating']),
                        ExpectedSpan::named('send event')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => FlowOrderPlaced::class])->containing(
                            ExpectedSpan::named('route order.placed.handling')->ofKind(SpanKind::Producer)->containing(
                                ExpectedSpan::named('process class@anonymous::on')->withAttributes([SpanAttribute::ENDPOINT_ID => 'order.placed.handling']),
                            ),
                        ),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function eventSourcedAggregateReturningAnEvent(): FlowScenario
    {
        $order = new #[EventSourcingAggregate] class () {
            use WithAggregateVersioning;

            #[Identifier]
            public string $orderId = '';

            #[CommandHandler('order.create', endpointId: 'order.creating')]
            public static function create(array $command): array
            {
                return [new FlowOrderPlaced($command['orderId'])];
            }

            #[Apply]
            public function applyPlaced(FlowOrderPlaced $event): void
            {
                $this->orderId = $event->orderId;
            }
        };
        $subscriber = self::orderPlacedSubscriber();

        return new FlowScenario('event-sourced aggregate returning an event', [$order::class, $subscriber::class, FlowOrderPlaced::class], [$subscriber], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('order.create', ['orderId' => '1'], metadata: $metadata))
                ->expect(
                    ExpectedSpan::named('send command')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'order.create'])->containing(
                        ExpectedSpan::named('process class@anonymous::create')->withAttributes([SpanAttribute::ENDPOINT_ID => 'order.creating']),
                        ExpectedSpan::named('send event')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => FlowOrderPlaced::class])->containing(
                            ExpectedSpan::named('route order.placed.handling')->ofKind(SpanKind::Producer)->containing(
                                ExpectedSpan::named('process class@anonymous::on')->withAttributes([SpanAttribute::ENDPOINT_ID => 'order.placed.handling']),
                            ),
                        ),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function sagaStartedByAnEventSendingACommand(): FlowScenario
    {
        $saga = new #[Saga] class () {
            #[Identifier]
            public string $orderId = '';

            #[EventHandler(endpointId: 'shipping.starting')]
            public static function start(FlowOrderPlaced $event, CommandBus $commandBus): self
            {
                $saga = new self();
                $saga->orderId = $event->orderId;
                $commandBus->sendWithRouting('shipping.prepare', $event->orderId);

                return $saga;
            }
        };
        $shipping = new class () {
            #[CommandHandler('shipping.prepare', endpointId: 'shipping.preparing')]
            public function prepare(string $orderId): void
            {
            }
        };

        return new FlowScenario('saga started by an event sending a command', [$saga::class, $shipping::class, FlowOrderPlaced::class], [$shipping], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->publishEvent(new FlowOrderPlaced('1'), metadata: $metadata))
                ->expect(
                    ExpectedSpan::named('send event')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => FlowOrderPlaced::class])->containing(
                        ExpectedSpan::named('route shipping.starting')->ofKind(SpanKind::Producer)->containing(
                            ExpectedSpan::named('process class@anonymous::start')->withAttributes([SpanAttribute::ENDPOINT_ID => 'shipping.starting'])->containing(
                                ExpectedSpan::named('send command')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'shipping.prepare'])->containing(
                                    ExpectedSpan::named('process class@anonymous::prepare')->withAttributes([SpanAttribute::ENDPOINT_ID => 'shipping.preparing']),
                                ),
                            ),
                        ),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function sagaContinuedByASecondEventMappedToIt(): FlowScenario
    {
        $saga = self::shippingSaga();

        return new FlowScenario('saga continued by a second event mapped to it', [$saga::class, FlowOrderPlaced::class, FlowPaymentReceived::class], [], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->publishEvent(new FlowOrderPlaced('1'), metadata: $metadata))
                ->expect(ExpectedSpan::named('send event')->containing(ExpectedSpan::named('route shipping.starting')->containing(ExpectedSpan::named('process class@anonymous::start'))))
                ->withNoOtherSpans(),
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->publishEvent(new FlowPaymentReceived('1'), metadata: $metadata))
                ->expect(
                    ExpectedSpan::named('send event')->asRoot()->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => FlowPaymentReceived::class])->containing(
                        ExpectedSpan::named('route shipping.paying')->containing(
                            ExpectedSpan::named('process class@anonymous::whenPaid')->withAttributes([SpanAttribute::ENDPOINT_ID => 'shipping.paying']),
                        ),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function eventForAnUnknownSagaIsDropped(): FlowScenario
    {
        $saga = self::shippingSaga();

        return new FlowScenario('event for an unknown saga is dropped', [$saga::class, FlowOrderPlaced::class, FlowPaymentReceived::class], [], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->publishEvent(new FlowPaymentReceived('unknown'), metadata: $metadata))
                ->expect(ExpectedSpan::named('send event')->containing(ExpectedSpan::named('route shipping.paying')->withStatus(SpanStatus::Ok)))
                ->expectNoSpanNamed('process class@anonymous::whenPaid')
                ->withNoOtherSpans(),
        ]);
    }

    private static function eventSourcedAggregateFeedingAProjection(): FlowScenario
    {
        $projection = new #[Projection('order_list'), FromAggregateStream(FlowOrder::class)] class () {
            #[EventHandler(endpointId: 'order_list.placed')]
            public function whenPlaced(FlowOrderPlaced $event): void
            {
            }
        };

        return new FlowScenario('event-sourced aggregate feeding a projection', [FlowOrder::class, FlowOrderPlaced::class, $projection::class], [$projection], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('flow_order.place', ['orderId' => '1'], metadata: $metadata))
                ->expect(
                    ExpectedSpan::named('send command')->containing(
                        ExpectedSpan::named('process FlowOrder::place')->withAttributes([SpanAttribute::ENDPOINT_ID => 'flow_order.placing']),
                        ExpectedSpan::named('send event')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => FlowOrderPlaced::class])->containing(
                            ExpectedSpan::named('route order_list')->ofKind(SpanKind::Producer)->containing(
                                ExpectedSpan::named('process order_list')->ofKind(SpanKind::Consumer)->withAttributes([SpanAttribute::PROJECTION_NAME => 'order_list'])->containing(
                                    ExpectedSpan::named('process class@anonymous::whenPlaced'),
                                ),
                            ),
                        ),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function shippingSaga(): object
    {
        return new #[Saga] class () {
            #[Identifier]
            public string $orderId = '';

            #[EventHandler(endpointId: 'shipping.starting')]
            public static function start(FlowOrderPlaced $event): self
            {
                $saga = new self();
                $saga->orderId = $event->orderId;

                return $saga;
            }

            #[EventHandler(endpointId: 'shipping.paying', dropMessageOnNotFound: true)]
            public function whenPaid(FlowPaymentReceived $event): void
            {
            }
        };
    }

    private static function orderPlacedSubscriber(): object
    {
        return new class () {
            #[EventHandler(endpointId: 'order.placed.handling')]
            public function on(FlowOrderPlaced $event): void
            {
            }
        };
    }
}
