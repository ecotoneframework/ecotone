<?php

declare(strict_types=1);

namespace Ecotone\Test\Flow;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Tracing\SpanAttribute;
use Ecotone\Api\Tracing\SpanKind;
use Ecotone\Api\Tracing\SpanStatus;
use RuntimeException;

/**
 * licence Apache-2.0
 */
final class FailureFlowScenarios
{
    /**
     * @return list<FlowScenario>
     */
    public static function all(): array
    {
        return [
            self::synchronousHandlerFailing(),
            self::synchronousSubscriberFailingFailsItsRoute(),
            self::asynchronousHandlerFailingIntoTheErrorChannel(),
        ];
    }

    private static function synchronousHandlerFailing(): FlowScenario
    {
        $orders = new class () {
            #[CommandHandler('order.place', endpointId: 'order.placing')]
            public function place(string $orderId): void
            {
                throw new RuntimeException('Order cannot be placed');
            }
        };

        return new FlowScenario('synchronous handler failing', [$orders::class], [$orders], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('order.place', '1', metadata: $metadata))
                ->failingWith('Order cannot be placed')
                ->expect(
                    ExpectedSpan::named('send command')->withStatus(SpanStatus::Error, 'Order cannot be placed')->withEventStartingWith('exception', self::exceptionRecorded())->containing(
                        ExpectedSpan::named('process class@anonymous::place')->withStatus(SpanStatus::Error, 'Order cannot be placed')->withEventStartingWith('exception', self::exceptionRecorded())->withAttributes([SpanAttribute::ENDPOINT_ID => 'order.placing']),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function synchronousSubscriberFailingFailsItsRoute(): FlowScenario
    {
        $notifier = new class () {
            #[EventHandler('merchant.created', endpointId: 'merchant.notifying')]
            public function notify(string $merchantId): void
            {
                throw new RuntimeException('Notification failed');
            }
        };

        return new FlowScenario('synchronous subscriber failing fails its route', [$notifier::class], [$notifier], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->publishEventWithRouting('merchant.created', '1', metadata: $metadata))
                ->failingWith('Notification failed')
                ->expect(
                    ExpectedSpan::named('send event')->withStatus(SpanStatus::Error)->containing(
                        ExpectedSpan::named('route merchant.notifying')->ofKind(SpanKind::Producer)->withStatus(SpanStatus::Error)->containing(
                            ExpectedSpan::named('process class@anonymous::notify')->withStatus(SpanStatus::Error, 'Notification failed'),
                        ),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private static function exceptionRecorded(): array
    {
        return ['exception.type' => RuntimeException::class, 'exception.message' => 'Order cannot be placed'];
    }

    private static function asynchronousHandlerFailingIntoTheErrorChannel(): FlowScenario
    {
        $orders = new class () {
            #[Asynchronous('orders')]
            #[CommandHandler('order.place', endpointId: 'order.placing')]
            public function place(string $orderId): void
            {
                throw new RuntimeException('Order cannot be placed');
            }
        };

        return new FlowScenario('asynchronous handler failing into the error channel', [$orders::class], [$orders], [SimpleMessageChannelBuilder::createQueueChannel('orders'), ServiceConfiguration::createWithDefaults()->withDefaultErrorChannel('errorChannel')], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('order.place', '1', metadata: $metadata))->leavingWorkPending()
                ->expect(
                    ExpectedSpan::named('send command')->withStatus(SpanStatus::Ok)->containing(
                        ExpectedSpan::named('send orders'),
                    ),
                )
                ->withNoOtherSpans(),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('orders', ExecutionPollingMetadata::createWithTestingSetup(stopOnError: false)))
                ->expect(
                    ExpectedSpan::named('receive orders')->withStatus(SpanStatus::Error)->containing(
                        ExpectedSpan::named('process class@anonymous::place')->withStatus(SpanStatus::Error),
                    ),
                ),
        ]);
    }
}
