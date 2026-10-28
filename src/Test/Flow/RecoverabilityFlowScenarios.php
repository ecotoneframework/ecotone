<?php

declare(strict_types=1);

namespace Ecotone\Test\Flow;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\ExtensionObject\ErrorHandlerConfiguration;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\FinalFailureStrategy;
use Ecotone\Api\ExtensionObject\InstantRetryConfiguration;
use Ecotone\Api\ExtensionObject\RetryTemplateBuilder;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Scheduling\TimeSpan;
use Ecotone\Api\Tracing\SpanAttribute;
use Ecotone\Api\Tracing\SpanKind;
use Ecotone\Api\Tracing\SpanStatus;
use RuntimeException;

/**
 * licence Apache-2.0
 */
final class RecoverabilityFlowScenarios
{
    /**
     * @return list<FlowScenario>
     */
    public static function all(): array
    {
        return [
            self::delayedMessageConsumedOnceItsDelayPassed(),
            self::failureRetriedWithDelayThenSentToDeadLetter(),
            self::commandBusRetriesInstantlyUntilTheHandlerSucceeds(),
            self::failedMessageIgnoredByItsChannel(),
        ];
    }

    private static function delayedMessageConsumedOnceItsDelayPassed(): FlowScenario
    {
        $orders = self::asynchronousOrders();

        return new FlowScenario('delayed message is consumed once its delay passed', [$orders::class], [$orders], [SimpleMessageChannelBuilder::createQueueChannel('orders')], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('order.place', '1', metadata: [...$metadata, MessageHeaders::DELIVERY_DELAY => 60000]))
                ->expect(self::queued())
                ->withNoOtherSpans()
                ->leavingWorkPending(),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('orders', ExecutionPollingMetadata::createWithTestingSetup()))
                ->expectNoSpanNamed('receive orders')
                ->withNoOtherSpans()
                ->leavingWorkPending(),
            FlowStage::when(function (FlowTestSupport $ecotone): void {
                $ecotone->advanceTimeBy(TimeSpan::withMinutes(2));
                $ecotone->run('orders', ExecutionPollingMetadata::createWithTestingSetup());
            })
                ->expect(
                    ExpectedSpan::named('send orders')->containing(
                        ExpectedSpan::named('receive orders')->withStatus(SpanStatus::Ok)->containing(
                            ExpectedSpan::named('process class@anonymous::place')->withAttributes([SpanAttribute::ENDPOINT_ID => 'order.placing']),
                        ),
                    ),
                ),
        ]);
    }

    private static function failureRetriedWithDelayThenSentToDeadLetter(): FlowScenario
    {
        $orders = self::failingOrders();

        return new FlowScenario('failure is retried with a delay, then sent to the dead letter', [$orders::class], [$orders], [
            SimpleMessageChannelBuilder::createQueueChannel('orders'),
            SimpleMessageChannelBuilder::createQueueChannel('dead_letter'),
            ErrorHandlerConfiguration::createWithDeadLetterChannel('errorChannel', RetryTemplateBuilder::fixedBackOff(0)->maxRetries(1), 'dead_letter'),
            InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false),
            ServiceConfiguration::createWithDefaults()->withDefaultErrorChannel('errorChannel'),
        ], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('order.place', '1', metadata: $metadata))
                ->expect(self::queued())
                ->withNoOtherSpans()
                ->leavingWorkPending(),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('orders', ExecutionPollingMetadata::createWithFinishWhenNoMessages(stopOnError: false)))
                ->expect(
                    ExpectedSpan::named('receive orders')->withStatus(SpanStatus::Error)->containing(
                        ExpectedSpan::named('process class@anonymous::place')->withStatus(SpanStatus::Error, 'Order cannot be placed'),
                        ExpectedSpan::named('send orders')->ofKind(SpanKind::Producer)->containing(
                            ExpectedSpan::named('receive orders')->withStatus(SpanStatus::Error)->containing(
                                ExpectedSpan::named('process class@anonymous::place')->withStatus(SpanStatus::Error),
                                ExpectedSpan::named('send dead_letter')->ofKind(SpanKind::Producer)->withStatus(SpanStatus::Ok),
                            ),
                        ),
                    ),
                ),
        ]);
    }

    private static function commandBusRetriesInstantlyUntilTheHandlerSucceeds(): FlowScenario
    {
        $orders = new class () {
            private int $attempts = 0;

            #[CommandHandler('order.place', endpointId: 'order.placing')]
            public function place(string $orderId): void
            {
                if (++$this->attempts < 2) {
                    throw new RuntimeException('Order storage busy');
                }
            }
        };

        return new FlowScenario('command bus retries instantly until the handler succeeds', [$orders::class], [$orders], [
            InstantRetryConfiguration::createWithDefaults()->withCommandBusRetry(true, 2),
        ], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('order.place', '1', metadata: $metadata))
                ->expect(
                    ExpectedSpan::named('send command')->withStatus(SpanStatus::Ok)->containing(
                        ExpectedSpan::named('process class@anonymous::place')->withStatus(SpanStatus::Error, 'Order storage busy'),
                        ExpectedSpan::named('process class@anonymous::place')->withStatus(SpanStatus::Ok),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function failedMessageIgnoredByItsChannel(): FlowScenario
    {
        $orders = self::failingOrders();

        return new FlowScenario('failed message is ignored by its channel', [$orders::class], [$orders], [
            SimpleMessageChannelBuilder::createQueueChannel('orders', finalFailureStrategy: FinalFailureStrategy::IGNORE),
            InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false),
        ], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('order.place', '1', metadata: $metadata))
                ->expect(self::queued())
                ->withNoOtherSpans()
                ->leavingWorkPending(),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('orders', ExecutionPollingMetadata::createWithTestingSetup(stopOnError: false)))
                ->expect(
                    ExpectedSpan::named('receive orders')->withStatus(SpanStatus::Error)->containing(
                        ExpectedSpan::named('process class@anonymous::place')->withStatus(SpanStatus::Error, 'Order cannot be placed'),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function queued(): ExpectedSpan
    {
        return ExpectedSpan::named('send command')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'order.place'])->containing(
            ExpectedSpan::named('send orders')->ofKind(SpanKind::Producer),
        );
    }

    private static function asynchronousOrders(): object
    {
        return new class () {
            #[Asynchronous('orders')]
            #[CommandHandler('order.place', endpointId: 'order.placing')]
            public function place(string $orderId): void
            {
            }
        };
    }

    private static function failingOrders(): object
    {
        return new class () {
            #[Asynchronous('orders')]
            #[CommandHandler('order.place', endpointId: 'order.placing')]
            public function place(string $orderId): void
            {
                throw new RuntimeException('Order cannot be placed');
            }
        };
    }
}
