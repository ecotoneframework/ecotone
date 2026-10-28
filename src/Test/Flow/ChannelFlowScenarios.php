<?php

declare(strict_types=1);

namespace Ecotone\Test\Flow;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\ExtensionObject\CombinedMessageChannel;
use Ecotone\Api\ExtensionObject\DynamicMessageChannelBuilder;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Tracing\SpanAttribute;
use Ecotone\Api\Tracing\SpanKind;

/**
 * licence Apache-2.0
 */
final class ChannelFlowScenarios
{
    /**
     * @return list<FlowScenario>
     */
    public static function all(): array
    {
        return [
            self::combinedChannelPassesThroughEachChannelInOrder(),
            self::asynchronousEndpointOnTwoChannelsPassesThroughBoth(),
            self::dynamicChannelSendsRoundRobin(),
        ];
    }

    private static function combinedChannelPassesThroughEachChannelInOrder(): FlowScenario
    {
        $orders = new class () {
            #[Asynchronous('outbox_then_orders')]
            #[CommandHandler('order.place', endpointId: 'order.placing')]
            public function place(string $orderId): void
            {
            }
        };

        return new FlowScenario('combined channel passes the message through each channel in order', [$orders::class], [$orders], [
            SimpleMessageChannelBuilder::createQueueChannel('outbox'),
            SimpleMessageChannelBuilder::createQueueChannel('orders'),
            CombinedMessageChannel::create('outbox_then_orders', ['outbox', 'orders']),
        ], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('order.place', '1', metadata: $metadata))
                ->expect(ExpectedSpan::named('send command')->containing(ExpectedSpan::named('send outbox')->ofKind(SpanKind::Producer)))
                ->withNoOtherSpans()
                ->leavingWorkPending(),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('outbox', ExecutionPollingMetadata::createWithTestingSetup()))
                ->expect(ExpectedSpan::named('receive outbox')->ofKind(SpanKind::Consumer)->containing(ExpectedSpan::named('send orders')->ofKind(SpanKind::Producer)))
                ->expectNoSpanNamed('process class@anonymous::place')
                ->withNoOtherSpans()
                ->leavingWorkPending(),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('orders', ExecutionPollingMetadata::createWithTestingSetup()))
                ->expect(
                    ExpectedSpan::named('send orders')->containing(
                        ExpectedSpan::named('receive orders')->ofKind(SpanKind::Consumer)->containing(
                            ExpectedSpan::named('process class@anonymous::place')->withAttributes([SpanAttribute::ENDPOINT_ID => 'order.placing']),
                        ),
                    ),
                ),
        ]);
    }

    private static function asynchronousEndpointOnTwoChannelsPassesThroughBoth(): FlowScenario
    {
        $orders = new class () {
            #[Asynchronous(['outbox', 'orders'])]
            #[CommandHandler('order.place', endpointId: 'order.placing')]
            public function place(string $orderId): void
            {
            }
        };

        return new FlowScenario('asynchronous endpoint on two channels passes the message through both', [$orders::class], [$orders], [
            SimpleMessageChannelBuilder::createQueueChannel('outbox'),
            SimpleMessageChannelBuilder::createQueueChannel('orders'),
        ], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('order.place', '1', metadata: $metadata))
                ->expect(ExpectedSpan::named('send command')->containing(ExpectedSpan::named('send outbox')->ofKind(SpanKind::Producer)))
                ->withNoOtherSpans()
                ->leavingWorkPending(),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('outbox', ExecutionPollingMetadata::createWithTestingSetup()))
                ->expect(ExpectedSpan::named('receive outbox')->ofKind(SpanKind::Consumer)->containing(ExpectedSpan::named('send orders')->ofKind(SpanKind::Producer)))
                ->expectNoSpanNamed('process class@anonymous::place')
                ->withNoOtherSpans()
                ->leavingWorkPending(),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('orders', ExecutionPollingMetadata::createWithTestingSetup()))
                ->expect(
                    ExpectedSpan::named('send orders')->containing(
                        ExpectedSpan::named('receive orders')->ofKind(SpanKind::Consumer)->containing(
                            ExpectedSpan::named('process class@anonymous::place')->withAttributes([SpanAttribute::ENDPOINT_ID => 'order.placing']),
                        ),
                    ),
                ),
        ]);
    }

    private static function dynamicChannelSendsRoundRobin(): FlowScenario
    {
        $orders = new class () {
            #[Asynchronous('orders')]
            #[CommandHandler('order.place', endpointId: 'order.placing')]
            public function place(string $orderId): void
            {
            }
        };

        return new FlowScenario('dynamic channel sends round robin to its channels', [$orders::class], [$orders], [
            SimpleMessageChannelBuilder::createQueueChannel('orders_a'),
            SimpleMessageChannelBuilder::createQueueChannel('orders_b'),
            DynamicMessageChannelBuilder::createRoundRobin('orders', ['orders_a', 'orders_b']),
        ], [
            FlowStage::when(function (FlowTestSupport $ecotone, array $metadata): void {
                $ecotone->sendCommandWithRouting('order.place', '1', metadata: [...$metadata, 'flowId' => '1']);
                $ecotone->sendCommandWithRouting('order.place', '2', metadata: [...$metadata, 'flowId' => '2']);
            })
                ->expect(
                    ExpectedSpan::named('send command')->withAttributes(['flowId' => '1'])->containing(ExpectedSpan::named('send orders'), ExpectedSpan::named('send orders_a')),
                    ExpectedSpan::named('send command')->withAttributes(['flowId' => '2'])->containing(ExpectedSpan::named('send orders'), ExpectedSpan::named('send orders_b')),
                )
                ->withNoOtherSpans()
                ->leavingWorkPending(),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('orders', ExecutionPollingMetadata::createWithFinishWhenNoMessages()))
                ->expect(
                    ExpectedSpan::named('send orders_a')->containing(ExpectedSpan::named('receive orders')->withAttributes(['flowId' => '1'])->containing(ExpectedSpan::named('process class@anonymous::place'))),
                    ExpectedSpan::named('send orders_b')->containing(ExpectedSpan::named('receive orders')->withAttributes(['flowId' => '2'])->containing(ExpectedSpan::named('process class@anonymous::place'))),
                ),
        ]);
    }
}
