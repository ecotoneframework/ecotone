<?php

declare(strict_types=1);

namespace Ecotone\Test\Flow;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\Poller;
use Ecotone\Api\Attribute\Scheduled;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Tracing\SpanAttribute;
use Ecotone\Api\Tracing\SpanKind;

/**
 * licence Apache-2.0
 */
final class AsynchronousFlowScenarios
{
    /**
     * @return list<FlowScenario>
     */
    public static function all(): array
    {
        return [
            self::eventPublishedDirectlyReachesAsynchronousSubscriber(),
            self::eventPublishedByCommandHandlerReachesAsynchronousSubscriber(),
            self::asynchronousCommandHandlerPublishingToAsynchronousSubscriber(),
            self::scheduledEndpointFeedingAnInternalHandler(),
        ];
    }

    private static function eventPublishedDirectlyReachesAsynchronousSubscriber(): FlowScenario
    {
        $notifier = new class () {
            #[Asynchronous('notifications')]
            #[EventHandler('merchant.created', endpointId: 'merchant.notifying')]
            public function notify(string $merchantId): void
            {
            }
        };

        return new FlowScenario(
            'event published directly reaches an asynchronous subscriber',
            [$notifier::class],
            [$notifier],
            [SimpleMessageChannelBuilder::createQueueChannel('notifications')],
            [
                FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->publishEventWithRouting('merchant.created', '1', metadata: $metadata))->leavingWorkPending()
                    ->expect(
                        ExpectedSpan::named('send event')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'merchant.created'])->containing(
                            ExpectedSpan::named('route merchant.notifying')->ofKind(SpanKind::Producer)->containing(
                                ExpectedSpan::named('send notifications')->ofKind(SpanKind::Producer)->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'notifications']),
                            ),
                        ),
                    )
                    ->expectNoSpanNamed('process class@anonymous::notify')
                    ->withNoOtherSpans(),
                FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('notifications', ExecutionPollingMetadata::createWithTestingSetup()))
                    ->expect(
                        ExpectedSpan::named('receive notifications')->ofKind(SpanKind::Consumer)->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'notifications'])->containing(
                            ExpectedSpan::named('process class@anonymous::notify')->withAttributes([SpanAttribute::ENDPOINT_ID => 'merchant.notifying']),
                        ),
                    )
                    ->withNoOtherSpans(),
            ],
        );
    }

    private static function eventPublishedByCommandHandlerReachesAsynchronousSubscriber(): FlowScenario
    {
        $orders = new class () {
            #[CommandHandler('order.place', endpointId: 'order.placing')]
            public function place(string $orderId, EventBus $eventBus): void
            {
                $eventBus->publishWithRouting('order.placed', $orderId);
            }
        };
        $notifier = new class () {
            #[Asynchronous('notifications')]
            #[EventHandler('order.placed', endpointId: 'order.notifying')]
            public function notify(string $orderId): void
            {
            }
        };

        return new FlowScenario(
            'event published by a command handler reaches an asynchronous subscriber',
            [$orders::class, $notifier::class],
            [$orders, $notifier],
            [SimpleMessageChannelBuilder::createQueueChannel('notifications')],
            [
                FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('order.place', '1', metadata: $metadata))->leavingWorkPending()
                    ->expect(
                        ExpectedSpan::named('send command')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'order.place'])->containing(
                            ExpectedSpan::named('process class@anonymous::place')->containing(
                                ExpectedSpan::named('send event')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'order.placed'])->containing(
                                    ExpectedSpan::named('route order.notifying')->ofKind(SpanKind::Producer),
                                ),
                            ),
                            ExpectedSpan::named('send notifications')->ofKind(SpanKind::Producer)->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'notifications']),
                        ),
                    )
                    ->expectNoSpanNamed('process class@anonymous::notify')
                    ->withNoOtherSpans(),
                FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('notifications', ExecutionPollingMetadata::createWithTestingSetup()))
                    ->expect(
                        ExpectedSpan::named('receive notifications')->ofKind(SpanKind::Consumer)->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'notifications'])->containing(
                            ExpectedSpan::named('process class@anonymous::notify')->withAttributes([SpanAttribute::ENDPOINT_ID => 'order.notifying']),
                        ),
                    )
                    ->withNoOtherSpans(),
            ],
        );
    }

    private static function asynchronousCommandHandlerPublishingToAsynchronousSubscriber(): FlowScenario
    {
        $users = new class () {
            #[Asynchronous('async_channel')]
            #[CommandHandler('user.register', endpointId: 'user.registration')]
            public function register(string $userId, EventBus $eventBus): void
            {
                $eventBus->publishWithRouting('user.registered', $userId);
            }
        };
        $notifier = new class () {
            #[Asynchronous('async_channel')]
            #[EventHandler('user.registered', endpointId: 'user.notifying')]
            public function notify(string $userId): void
            {
            }
        };

        return new FlowScenario(
            'asynchronous command handler publishing to an asynchronous subscriber on the same channel',
            [$users::class, $notifier::class],
            [$users, $notifier],
            [SimpleMessageChannelBuilder::createQueueChannel('async_channel')],
            [
                FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('user.register', '1', metadata: $metadata))->leavingWorkPending()
                    ->expect(
                        ExpectedSpan::named('send command')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'user.register'])->containing(
                            ExpectedSpan::named('send async_channel')->ofKind(SpanKind::Producer),
                        ),
                    )
                    ->expectNoSpanNamed('process class@anonymous::register')
                    ->withNoOtherSpans(),
                FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('async_channel', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 2)))
                    ->expect(
                        ExpectedSpan::named('receive async_channel')->ofKind(SpanKind::Consumer)->containing(
                            ExpectedSpan::named('process class@anonymous::register')->withAttributes([SpanAttribute::ENDPOINT_ID => 'user.registration'])->containing(
                                ExpectedSpan::named('send event')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'user.registered'])->containing(
                                    ExpectedSpan::named('route user.notifying')->ofKind(SpanKind::Producer),
                                ),
                            ),
                            ExpectedSpan::named('send async_channel')->ofKind(SpanKind::Producer)->containing(
                                ExpectedSpan::named('receive async_channel')->ofKind(SpanKind::Consumer)->containing(
                                    ExpectedSpan::named('process class@anonymous::notify')->withAttributes([SpanAttribute::ENDPOINT_ID => 'user.notifying']),
                                ),
                            ),
                        ),
                    )
                    ->withNoOtherSpans(),
            ],
        );
    }

    private static function scheduledEndpointFeedingAnInternalHandler(): FlowScenario
    {
        $workflow = new class () {
            #[Scheduled(requestChannelName: 'add', endpointId: 'scheduled_handler')]
            #[Poller(fixedRateInMilliseconds: 1)]
            public function produce(): int
            {
                return 1;
            }

            #[InternalHandler(inputChannelName: 'add', endpointId: 'adding')]
            public function add(): void
            {
            }
        };

        return new FlowScenario('scheduled endpoint feeding an internal handler', [$workflow::class], [$workflow], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('scheduled_handler', ExecutionPollingMetadata::createWithDefaults()->withExecutionAmountLimit(1)))
                ->expect(
                    ExpectedSpan::named('receive add')->ofKind(SpanKind::Consumer)->containing(
                        ExpectedSpan::named('process class@anonymous::add')->withAttributes([SpanAttribute::ENDPOINT_ID => 'adding']),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }
}
