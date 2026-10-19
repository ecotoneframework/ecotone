<?php

declare(strict_types=1);

namespace Ecotone\Test\Flow;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\Router;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Tracing\SpanAttribute;
use Ecotone\Api\Tracing\SpanKind;
use Ecotone\Test\Flow\Fixture\FlowOrderCancelled;
use Ecotone\Test\Flow\Fixture\FlowOrderEvent;

/**
 * licence Apache-2.0
 */
final class RoutingFlowScenarios
{
    /**
     * @return list<FlowScenario>
     */
    public static function all(): array
    {
        return [
            self::routerSendingToSeveralChannels(),
            self::eventReachingAWildcardSubscriber(),
            self::eventReachingASubscriberTypedWithItsInterface(),
            self::asynchronousAggregateActionOnAnExistingAggregate(),
        ];
    }

    private static function routerSendingToSeveralChannels(): FlowScenario
    {
        $shipments = new class () {
            #[CommandHandler('shipment.dispatch', outputChannelName: 'shipment.route', endpointId: 'shipment.dispatching')]
            public function dispatch(string $shipmentId): string
            {
                return $shipmentId;
            }

            #[Router('shipment.route')]
            public function route(string $shipmentId): array
            {
                return ['shipment.label', 'shipment.notify'];
            }

            #[InternalHandler('shipment.label', endpointId: 'shipment.labelling')]
            public function label(string $shipmentId): void
            {
            }

            #[InternalHandler('shipment.notify', endpointId: 'shipment.notifying')]
            public function notify(string $shipmentId): void
            {
            }
        };

        return new FlowScenario('router sending the command to several channels', [$shipments::class], [$shipments], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('shipment.dispatch', '1', metadata: $metadata))
                ->expect(
                    ExpectedSpan::named('send command')->containing(
                        ExpectedSpan::named('process class@anonymous::dispatch'),
                        ExpectedSpan::named('process class@anonymous::label')->withAttributes([SpanAttribute::ENDPOINT_ID => 'shipment.labelling']),
                        ExpectedSpan::named('process class@anonymous::notify')->withAttributes([SpanAttribute::ENDPOINT_ID => 'shipment.notifying']),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function eventReachingAWildcardSubscriber(): FlowScenario
    {
        $audit = new class () {
            #[EventHandler('order.*', endpointId: 'order.auditing')]
            public function audit(string $orderId): void
            {
            }
        };

        return new FlowScenario('event reaching a wildcard subscriber', [$audit::class], [$audit], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->publishEventWithRouting('order.placed', '1', metadata: $metadata))
                ->expect(
                    ExpectedSpan::named('send event')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'order.placed'])->containing(
                        ExpectedSpan::named('route order.auditing')->ofKind(SpanKind::Producer)->containing(
                            ExpectedSpan::named('process class@anonymous::audit')->withAttributes([SpanAttribute::ENDPOINT_ID => 'order.auditing']),
                        ),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function eventReachingASubscriberTypedWithItsInterface(): FlowScenario
    {
        $audit = new class () {
            #[EventHandler(endpointId: 'order.auditing')]
            public function audit(FlowOrderEvent $event): void
            {
            }
        };

        return new FlowScenario('event reaching a subscriber typed with its interface', [$audit::class, FlowOrderEvent::class, FlowOrderCancelled::class], [$audit], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->publishEvent(new FlowOrderCancelled('1'), metadata: $metadata))
                ->expect(
                    ExpectedSpan::named('send event')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => FlowOrderCancelled::class])->containing(
                        ExpectedSpan::named('route order.auditing')->containing(
                            ExpectedSpan::named('process class@anonymous::audit'),
                        ),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function asynchronousAggregateActionOnAnExistingAggregate(): FlowScenario
    {
        $order = new #[Aggregate] class () {
            #[Identifier]
            public string $orderId = '';

            #[CommandHandler('order.create', endpointId: 'order.creating')]
            public static function create(array $command): self
            {
                $order = new self();
                $order->orderId = $command['orderId'];

                return $order;
            }

            #[Asynchronous('orders')]
            #[CommandHandler('order.cancel', endpointId: 'order.cancelling')]
            public function cancel(): void
            {
            }
        };

        return new FlowScenario('asynchronous aggregate action on an existing aggregate', [$order::class], [], [SimpleMessageChannelBuilder::createQueueChannel('orders')], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('order.create', ['orderId' => '1'], metadata: $metadata))
                ->expect(ExpectedSpan::named('send command')->containing(ExpectedSpan::named('process class@anonymous::create')))
                ->withNoOtherSpans(),
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('order.cancel', metadata: [...$metadata, 'aggregate.id' => '1']))
                ->expect(ExpectedSpan::named('send command')->asRoot()->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'order.cancel'])->containing(ExpectedSpan::named('send orders')->ofKind(SpanKind::Producer)))
                ->expectNoSpanNamed('process class@anonymous::cancel')
                ->leavingWorkPending(),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('orders', ExecutionPollingMetadata::createWithTestingSetup()))
                ->expect(
                    ExpectedSpan::named('receive orders')->ofKind(SpanKind::Consumer)->containing(
                        ExpectedSpan::named('process class@anonymous::cancel')->withAttributes([SpanAttribute::ENDPOINT_ID => 'order.cancelling']),
                    ),
                ),
        ]);
    }
}
