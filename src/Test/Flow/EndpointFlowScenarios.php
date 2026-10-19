<?php

declare(strict_types=1);

namespace Ecotone\Test\Flow;

use Ecotone\Api\Attribute\After;
use Ecotone\Api\Attribute\Around;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\Reference;
use Ecotone\Api\Attribute\Transformer;
use Ecotone\Api\Attribute\WithoutMessageCollector;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\InstantRetryConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Interceptor\MethodInvocation;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Tracing\SpanAttribute;
use Ecotone\Api\Tracing\SpanKind;
use Ecotone\Api\Tracing\SpanStatus;
use Ecotone\Test\Flow\Fixture\FlowAudited;
use RuntimeException;

/**
 * licence Apache-2.0
 */
final class EndpointFlowScenarios
{
    /**
     * @return list<FlowScenario>
     */
    public static function all(): array
    {
        return [
            self::transformerReshapesTheMessageForTheNextHandler(),
            self::transformerReturningNothingEndsTheFlow(),
            self::aroundInterceptorWrapsTheHandler(),
            self::afterInterceptorSendsACommandOnceTheHandlerReturned(),
            self::failedHandlerDropsTheMessagesItCollected(),
            self::handlerWithoutCollectorSendsImmediatelyEvenWhenItFails(),
            self::handlerWithCollectorSendsWhatItPublishedOnceItSucceeds(),
        ];
    }

    private static function transformerReshapesTheMessageForTheNextHandler(): FlowScenario
    {
        $orders = new class () {
            #[CommandHandler('order.place', outputChannelName: 'order.enrich', endpointId: 'order.placing')]
            public function place(string $orderId): string
            {
                return $orderId;
            }

            #[Transformer('order.enrich', outputChannelName: 'order.store', endpointId: 'order.enriching')]
            public function enrich(string $orderId): string
            {
                return 'order-' . $orderId;
            }

            #[InternalHandler('order.store', endpointId: 'order.storing')]
            public function store(string $order): void
            {
            }
        };

        return new FlowScenario('transformer reshapes the message for the next handler', [$orders::class], [$orders], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('order.place', '1', metadata: $metadata))
                ->expect(
                    ExpectedSpan::named('send command')->containing(
                        ExpectedSpan::named('process class@anonymous::place')->withAttributes([SpanAttribute::ENDPOINT_ID => 'order.placing']),
                        ExpectedSpan::named('process class@anonymous::store')->withAttributes([SpanAttribute::ENDPOINT_ID => 'order.storing']),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function transformerReturningNothingEndsTheFlow(): FlowScenario
    {
        $orders = new class () {
            #[CommandHandler('order.place', outputChannelName: 'order.filter', endpointId: 'order.placing')]
            public function place(string $orderId): string
            {
                return $orderId;
            }

            #[Transformer('order.filter', outputChannelName: 'order.store', endpointId: 'order.filtering')]
            public function filter(string $orderId): ?string
            {
                return null;
            }

            #[InternalHandler('order.store', endpointId: 'order.storing')]
            public function store(string $orderId): void
            {
            }
        };

        return new FlowScenario('transformer returning nothing ends the flow', [$orders::class], [$orders], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('order.place', '1', metadata: $metadata))
                ->expect(ExpectedSpan::named('send command')->containing(ExpectedSpan::named('process class@anonymous::place')))
                ->expectNoSpanNamed('process class@anonymous::store')
                ->withNoOtherSpans(),
        ]);
    }

    private static function aroundInterceptorWrapsTheHandler(): FlowScenario
    {
        $orders = new class () {
            #[FlowAudited]
            #[CommandHandler('order.place', endpointId: 'order.placing')]
            public function place(string $orderId): void
            {
            }
        };
        $timing = new class () {
            #[Around(pointcut: FlowAudited::class)]
            public function time(MethodInvocation $invocation): mixed
            {
                return $invocation->proceed();
            }
        };

        return new FlowScenario('around interceptor wraps the handler', [$orders::class, $timing::class, FlowAudited::class], [$orders, $timing], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('order.place', '1', metadata: $metadata))
                ->expect(ExpectedSpan::named('send command')->containing(ExpectedSpan::named('process class@anonymous::place')->withAttributes([SpanAttribute::ENDPOINT_ID => 'order.placing'])))
                ->withNoOtherSpans(),
        ]);
    }

    private static function afterInterceptorSendsACommandOnceTheHandlerReturned(): FlowScenario
    {
        $orders = new class () {
            #[FlowAudited]
            #[CommandHandler('order.place', endpointId: 'order.placing')]
            public function place(string $orderId): string
            {
                return $orderId;
            }

            #[CommandHandler('audit.record', endpointId: 'audit.recording')]
            public function record(string $orderId): void
            {
            }
        };
        $auditing = new class () {
            #[After(pointcut: FlowAudited::class)]
            public function audit(string $orderId, #[Reference] CommandBus $commandBus): void
            {
                $commandBus->sendWithRouting('audit.record', $orderId);
            }
        };

        return new FlowScenario('after interceptor sends a command once the handler returned', [$orders::class, $auditing::class, FlowAudited::class], [$orders, $auditing], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('order.place', '1', metadata: $metadata))
                ->expect(
                    ExpectedSpan::named('send command')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'order.place'])->containing(
                        ExpectedSpan::named('process class@anonymous::place'),
                        ExpectedSpan::named('send command')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'audit.record'])->containing(
                            ExpectedSpan::named('process class@anonymous::record')->withAttributes([SpanAttribute::ENDPOINT_ID => 'audit.recording']),
                        ),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function failedHandlerDropsTheMessagesItCollected(): FlowScenario
    {
        [$orders, $notifier] = self::publishingThenFailing(withCollector: true);

        return new FlowScenario('failed handler drops the messages it collected', [$orders::class, $notifier::class], [$orders, $notifier], [
            SimpleMessageChannelBuilder::createQueueChannel('orders'),
            SimpleMessageChannelBuilder::createQueueChannel('notifications'),
            InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false),
        ], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('order.place', '1', metadata: $metadata))
                ->expect(ExpectedSpan::named('send command')->containing(ExpectedSpan::named('send orders')->ofKind(SpanKind::Producer)))
                ->withNoOtherSpans()
                ->leavingWorkPending(),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('orders', ExecutionPollingMetadata::createWithTestingSetup()))
                ->failingWith('Order cannot be stored')
                ->expect(
                    ExpectedSpan::named('receive orders')->withStatus(SpanStatus::Error)->containing(
                        ExpectedSpan::named('process class@anonymous::place')->withStatus(SpanStatus::Error)->containing(
                            ExpectedSpan::named('send event')->containing(ExpectedSpan::named('route order.notifying')),
                        ),
                    ),
                )
                ->expectNoSpanNamed('send notifications')
                ->withNoOtherSpans(),
        ]);
    }

    private static function handlerWithoutCollectorSendsImmediatelyEvenWhenItFails(): FlowScenario
    {
        [$orders, $notifier] = self::publishingThenFailing(withCollector: false);

        return new FlowScenario('handler without the message collector sends immediately even when it fails', [$orders::class, $notifier::class], [$orders, $notifier], [
            SimpleMessageChannelBuilder::createQueueChannel('orders'),
            SimpleMessageChannelBuilder::createQueueChannel('notifications'),
            InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false),
        ], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('order.place', '1', metadata: $metadata))
                ->expect(ExpectedSpan::named('send command')->containing(ExpectedSpan::named('send orders')->ofKind(SpanKind::Producer)))
                ->withNoOtherSpans()
                ->leavingWorkPending(),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('orders', ExecutionPollingMetadata::createWithTestingSetup()))
                ->failingWith('Order cannot be stored')
                ->expect(
                    ExpectedSpan::named('receive orders')->withStatus(SpanStatus::Error)->containing(
                        ExpectedSpan::named('process class@anonymous::place')->withStatus(SpanStatus::Error)->containing(
                            ExpectedSpan::named('send event')->containing(
                                ExpectedSpan::named('route order.notifying')->containing(
                                    ExpectedSpan::named('send notifications')->ofKind(SpanKind::Producer)->withStatus(SpanStatus::Ok),
                                ),
                            ),
                        ),
                    ),
                ),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('notifications', ExecutionPollingMetadata::createWithTestingSetup()))
                ->leavingWorkPending()
                ->expect(
                    ExpectedSpan::named('receive notifications')->withStatus(SpanStatus::Ok)->containing(
                        ExpectedSpan::named('process class@anonymous::notify')->withAttributes([SpanAttribute::ENDPOINT_ID => 'order.notifying']),
                    ),
                ),
        ]);
    }

    private static function handlerWithCollectorSendsWhatItPublishedOnceItSucceeds(): FlowScenario
    {
        $notifier = new class () {
            #[Asynchronous('notifications')]
            #[EventHandler('order.placed', endpointId: 'order.notifying')]
            public function notify(string $orderId): void
            {
            }
        };
        $orders = new class () {
            #[Asynchronous('orders')]
            #[CommandHandler('order.place', endpointId: 'order.placing')]
            public function place(string $orderId, EventBus $eventBus): void
            {
                $eventBus->publishWithRouting('order.placed', $orderId);
            }
        };

        return new FlowScenario('handler with the message collector sends what it published once it succeeds', [$orders::class, $notifier::class], [$orders, $notifier], [
            SimpleMessageChannelBuilder::createQueueChannel('orders'),
            SimpleMessageChannelBuilder::createQueueChannel('notifications'),
        ], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('order.place', '1', metadata: $metadata))
                ->expect(ExpectedSpan::named('send command')->containing(ExpectedSpan::named('send orders')->ofKind(SpanKind::Producer)))
                ->withNoOtherSpans()
                ->leavingWorkPending(),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('orders', ExecutionPollingMetadata::createWithTestingSetup()))
                ->expect(
                    ExpectedSpan::named('receive orders')->withStatus(SpanStatus::Ok)->containing(
                        ExpectedSpan::named('process class@anonymous::place')->containing(
                            ExpectedSpan::named('send event')->containing(
                                ExpectedSpan::named('route order.notifying')->withAttributes([SpanAttribute::ROUTE_COLLECTED => true])->withoutAttributes(SpanAttribute::ROUTE_UNDELIVERED),
                            ),
                        ),
                        ExpectedSpan::named('send notifications')->ofKind(SpanKind::Producer)->withStatus(SpanStatus::Ok),
                    ),
                )
                ->leavingWorkPending(),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('notifications', ExecutionPollingMetadata::createWithTestingSetup()))
                ->expect(
                    ExpectedSpan::named('send notifications')->containing(
                        ExpectedSpan::named('receive notifications')->containing(
                            ExpectedSpan::named('process class@anonymous::notify')->withAttributes([SpanAttribute::ENDPOINT_ID => 'order.notifying']),
                        ),
                    ),
                ),
        ]);
    }

    /**
     * @return array{object, object}
     */
    private static function publishingThenFailing(bool $withCollector): array
    {
        $notifier = new class () {
            #[Asynchronous('notifications')]
            #[EventHandler('order.placed', endpointId: 'order.notifying')]
            public function notify(string $orderId): void
            {
            }
        };
        $orders = $withCollector
            ? new class () {
                #[Asynchronous('orders')]
                #[CommandHandler('order.place', endpointId: 'order.placing')]
                public function place(string $orderId, EventBus $eventBus): void
                {
                    $eventBus->publishWithRouting('order.placed', $orderId);
                    throw new RuntimeException('Order cannot be stored');
                }
            }
        : new class () {
            #[Asynchronous('orders', asynchronousExecution: [new WithoutMessageCollector()])]
            #[CommandHandler('order.place', endpointId: 'order.placing')]
            public function place(string $orderId, EventBus $eventBus): void
            {
                $eventBus->publishWithRouting('order.placed', $orderId);
                throw new RuntimeException('Order cannot be stored');
            }
        };

        return [$orders, $notifier];
    }
}
