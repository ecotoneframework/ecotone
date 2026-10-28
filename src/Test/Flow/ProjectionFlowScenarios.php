<?php

declare(strict_types=1);

namespace Ecotone\Test\Flow;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Polling;
use Ecotone\Api\EventSourcing\Event;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Projecting\FromStream;
use Ecotone\Api\Projecting\Partitioned;
use Ecotone\Api\Projecting\Projection;
use Ecotone\Api\Projecting\ProjectionBackfill;
use Ecotone\Api\Projecting\ProjectionRebuild;
use Ecotone\Api\Tracing\SpanAttribute;
use Ecotone\Api\Tracing\SpanKind;
use Ecotone\Projecting\ProjectionOperationEndpoint;

/**
 * licence Apache-2.0
 */
final class ProjectionFlowScenarios
{
    /**
     * @return list<FlowScenario>
     */
    public static function all(): array
    {
        return [
            self::eventDrivenProjection(),
            self::asynchronousProjection(),
            self::pollingProjectionReadingItsStream(),
            self::partitionedProjectionTriggeredForOnePartition(),
            self::projectionOperation('projection triggered', ProjectionOperationEndpoint::TRIGGER, fn (FlowTestSupport $ecotone) => $ecotone->triggerProjection('orders')),
            self::projectionOperation('projection reset replays its stream', ProjectionOperationEndpoint::RESET, fn (FlowTestSupport $ecotone) => $ecotone->resetProjection('orders')),
            self::projectionOperation('projection backfilled from the console', ProjectionOperationEndpoint::BACKFILL, fn (FlowTestSupport $ecotone) => $ecotone->runConsoleCommand('ecotone:projection:backfill', ['name' => 'orders'])),
            self::projectionOperation('projection rebuilt from the console', ProjectionOperationEndpoint::REBUILD, fn (FlowTestSupport $ecotone) => $ecotone->runConsoleCommand('ecotone:projection:rebuild', ['name' => 'orders'])),
            self::projectionBackfilledAsynchronously(),
            self::projectionRebuiltAsynchronously(),
            self::projectionInitializedAndDeleted(),
        ];
    }

    private static function projectionOperation(string $name, string $operation, callable $action): FlowScenario
    {
        $projection = new #[Projection('orders'), FromStream('orders_stream')] class () {
            #[EventHandler('order.placed')]
            public function whenPlaced(array $event): void
            {
            }
        };

        return new FlowScenario($name, [$projection::class], [$projection], [], [
            FlowStage::when(function (FlowTestSupport $ecotone) use ($action): void {
                $ecotone->withEventStream('orders_stream', [Event::createWithType('order.placed', ['orderId' => '1'])]);
                $action($ecotone);
            })
                ->expect(
                    ExpectedSpan::named($operation . ' orders')->asRoot()->ofKind(SpanKind::Server)->withAttributes([SpanAttribute::PROJECTION_NAME => 'orders', SpanAttribute::PROJECTION_OPERATION => $operation])->containing(
                        self::projectionHandled($operation),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function projectionBackfilledAsynchronously(): FlowScenario
    {
        $projection = new #[Projection('orders'), FromStream('orders_stream'), ProjectionBackfill(asyncChannelName: 'backfill')] class () {
            #[EventHandler('order.placed')]
            public function whenPlaced(array $event): void
            {
            }
        };

        return self::projectionReplayedAsynchronously('projection backfilled asynchronously', ProjectionOperationEndpoint::BACKFILL, $projection);
    }

    private static function projectionRebuiltAsynchronously(): FlowScenario
    {
        $projection = new #[Projection('orders'), FromStream('orders_stream'), ProjectionRebuild(asyncChannelName: 'rebuild')] class () {
            #[EventHandler('order.placed')]
            public function whenPlaced(array $event): void
            {
            }
        };

        return self::projectionReplayedAsynchronously('projection rebuilt asynchronously', ProjectionOperationEndpoint::REBUILD, $projection);
    }

    private static function projectionReplayedAsynchronously(string $name, string $operation, object $projection): FlowScenario
    {
        return new FlowScenario($name, [$projection::class], [$projection], [SimpleMessageChannelBuilder::createQueueChannel($operation)], [
            FlowStage::when(function (FlowTestSupport $ecotone) use ($operation): void {
                $ecotone->withEventStream('orders_stream', [Event::createWithType('order.placed', ['orderId' => '1'])]);
                $ecotone->runConsoleCommand('ecotone:projection:' . $operation, ['name' => 'orders']);
            })->leavingWorkPending()
                ->expect(
                    ExpectedSpan::named($operation . ' orders')->asRoot()->ofKind(SpanKind::Server)->containing(
                        ExpectedSpan::named('send ' . $operation)->ofKind(SpanKind::Producer),
                    ),
                )
                ->expectNoSpanNamed('process class@anonymous::whenPlaced')
                ->withNoOtherSpans(),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run($operation, ExecutionPollingMetadata::createWithTestingSetup()))
                ->expect(
                    ExpectedSpan::named('receive ' . $operation)->ofKind(SpanKind::Consumer)->containing(
                        self::projectionHandled($operation),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function projectionHandled(string $operation): ExpectedSpan
    {
        $handled = ExpectedSpan::named('process class@anonymous::whenPlaced');
        if (! in_array($operation, [ProjectionOperationEndpoint::BACKFILL, ProjectionOperationEndpoint::REBUILD], true)) {
            return $handled;
        }

        return ExpectedSpan::named('process partition batch orders')->ofKind(SpanKind::Internal)->withAttributes([SpanAttribute::PROJECTION_NAME => 'orders'])->containing($handled);
    }

    private static function projectionInitializedAndDeleted(): FlowScenario
    {
        $projection = new #[Projection('orders'), FromStream('orders_stream')] class () {
            #[EventHandler('order.placed')]
            public function whenPlaced(array $event): void
            {
            }
        };

        return new FlowScenario('projection initialized, then deleted', [$projection::class], [$projection], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->initializeProjection('orders'))
                ->expect(ExpectedSpan::named('initialize orders')->asRoot()->ofKind(SpanKind::Server)->withAttributes([SpanAttribute::PROJECTION_OPERATION => ProjectionOperationEndpoint::INITIALIZE]))
                ->withNoOtherSpans(),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->deleteProjection('orders'))
                ->expect(ExpectedSpan::named('delete orders')->asRoot()->ofKind(SpanKind::Server)->withAttributes([SpanAttribute::PROJECTION_OPERATION => ProjectionOperationEndpoint::DELETE]))
                ->withNoOtherSpans(),
        ]);
    }

    private static function eventDrivenProjection(): FlowScenario
    {
        $projection = new #[Projection('orders'), FromStream('orders_stream')] class () {
            #[EventHandler('order.placed')]
            public function whenPlaced(array $event): void
            {
            }
        };

        return new FlowScenario('event-driven projection', [$projection::class], [$projection], [], [
            FlowStage::when(function (FlowTestSupport $ecotone, array $metadata): void {
                $ecotone->withEvents([Event::createWithType('order.placed', ['orderId' => '1'])]);
                $ecotone->publishEventWithRouting('order.placed', [], metadata: $metadata);
            })
                ->expect(
                    ExpectedSpan::named('send event')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'order.placed'])->containing(
                        ExpectedSpan::named('route orders')->ofKind(SpanKind::Producer)->withAttributes([SpanAttribute::PROJECTION_NAME => 'orders'])->containing(
                            ExpectedSpan::named('process orders')->ofKind(SpanKind::Consumer)->withAttributes([SpanAttribute::PROJECTION_NAME => 'orders']),
                        ),
                    ),
                ),
        ]);
    }

    private static function asynchronousProjection(): FlowScenario
    {
        $projection = new #[Projection('orders'), FromStream('orders_stream'), Asynchronous('projections')] class () {
            #[EventHandler('order.placed')]
            public function whenPlaced(array $event): void
            {
            }
        };

        return new FlowScenario('asynchronous projection', [$projection::class], [$projection], [SimpleMessageChannelBuilder::createQueueChannel('projections')], [
            FlowStage::when(function (FlowTestSupport $ecotone, array $metadata): void {
                $ecotone->withEvents([Event::createWithType('order.placed', ['orderId' => '1'])]);
                $ecotone->publishEventWithRouting('order.placed', [], metadata: $metadata);
            })->leavingWorkPending()
                ->expect(
                    ExpectedSpan::named('send event')->containing(
                        ExpectedSpan::named('route orders')->ofKind(SpanKind::Producer)->containing(
                            ExpectedSpan::named('send projections')->ofKind(SpanKind::Producer),
                        ),
                    ),
                )
                ->expectNoSpanNamed('process orders'),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('projections', ExecutionPollingMetadata::createWithTestingSetup()))
                ->expect(
                    ExpectedSpan::named('receive projections')->ofKind(SpanKind::Consumer)->containing(
                        ExpectedSpan::named('process orders')->ofKind(SpanKind::Consumer)->withAttributes([SpanAttribute::PROJECTION_NAME => 'orders']),
                    ),
                ),
        ]);
    }

    private static function pollingProjectionReadingItsStream(): FlowScenario
    {
        $projection = new #[Projection('order_board'), Polling('order_board_poller'), FromStream('board_stream')] class () {
            #[EventHandler('order.placed', endpointId: 'order_board.placed')]
            public function whenPlaced(array $event): void
            {
            }
        };

        return new FlowScenario('polling projection reading its stream', [$projection::class], [$projection], [], [
            FlowStage::when(function (FlowTestSupport $ecotone): void {
                $ecotone->withEventStream('board_stream', [Event::createWithType('order.placed', ['orderId' => '1'])]);
                $ecotone->run('order_board_poller', ExecutionPollingMetadata::createWithTestingSetup());
            })
                ->expect(
                    ExpectedSpan::named('poll order_board_poller')->asRoot()->ofKind(SpanKind::Consumer)->containing(
                        ExpectedSpan::named('process order_board')->ofKind(SpanKind::Consumer)->withAttributes([SpanAttribute::PROJECTION_NAME => 'order_board'])->containing(
                            ExpectedSpan::named('process class@anonymous::whenPlaced')->withAttributes([SpanAttribute::ENDPOINT_ID => 'order_board.placed']),
                        ),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function partitionedProjectionTriggeredForOnePartition(): FlowScenario
    {
        $projection = new #[Projection('tenant_orders'), FromStream('tenant_stream'), Partitioned('tenantId')] class () {
            #[EventHandler('order.placed', endpointId: 'tenant_orders.placed')]
            public function whenPlaced(array $event): void
            {
            }
        };

        return new FlowScenario('partitioned projection triggered for one partition', [$projection::class], [$projection], [], [
            FlowStage::when(function (FlowTestSupport $ecotone, array $metadata): void {
                $ecotone->withEvents([
                    Event::createWithType('order.placed', [], [MessageHeaders::EVENT_AGGREGATE_ID => 'order-1', 'tenantId' => 'tenant-1']),
                ]);
                $ecotone->publishEventWithRouting('order.placed', [], metadata: [...$metadata, MessageHeaders::EVENT_AGGREGATE_ID => 'order-1', 'tenantId' => 'tenant-1']);
            })
                ->expect(
                    ExpectedSpan::named('send event')->containing(
                        ExpectedSpan::named('route tenant_orders')->ofKind(SpanKind::Producer)->containing(
                            ExpectedSpan::named('process tenant_orders')->ofKind(SpanKind::Consumer)->containing(
                                ExpectedSpan::named('process class@anonymous::whenPlaced')->withAttributes([SpanAttribute::ENDPOINT_ID => 'tenant_orders.placed']),
                            ),
                        ),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }
}
