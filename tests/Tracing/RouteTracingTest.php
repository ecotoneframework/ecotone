<?php

declare(strict_types=1);

namespace Test\Ecotone\Tracing;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\EventSourcing\Event;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Projecting\FromStream;
use Ecotone\Api\Projecting\Projection;
use Ecotone\Api\Tracing\InMemorySpanRecorder;
use Ecotone\Api\Tracing\Span;
use Ecotone\Api\Tracing\SpanKind;
use Ecotone\Api\Tracing\SpanStatus;
use Ecotone\Test\LicenceTesting;
use RuntimeException;

/**
 * licence Apache-2.0
 * @internal
 */
final class RouteTracingTest extends TracingTestCase
{
    public function test_each_event_route_is_a_producer_span_between_the_event_bus_and_its_handler(): void
    {
        $recorder = new InMemorySpanRecorder();
        $subscriber = new class () {
            #[EventHandler('merchant.created', endpointId: 'merchant.notifier')]
            public function notify(string $merchantId): void
            {
            }
        };

        EcotoneLite::bootstrapFlowTesting(
            [$subscriber::class],
            [$subscriber, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration()],
        )
            ->publishEventWithRouting('merchant.created', '1');

        self::compareTreesByDetails(
            [
                [
                    'details' => ['name' => 'send event'],
                    'children' => [
                        [
                            'details' => [
                                'name' => 'route merchant.notifier',
                                'kind' => SpanKind::Producer,
                                'attributes' => ['messaging.destination.name' => 'merchant.notifier.target', 'ecotone.endpoint.id' => 'merchant.notifier'],
                            ],
                            'children' => [
                                ['details' => ['name' => 'process class@anonymous::notify', 'attributes' => ['ecotone.endpoint.id' => 'merchant.notifier']], 'children' => []],
                            ],
                        ],
                    ],
                ],
            ],
            self::buildTree($recorder->recordedSpans())
        );
    }

    public function test_a_failing_synchronous_handler_fails_its_route_span(): void
    {
        $recorder = new InMemorySpanRecorder();
        $subscriber = new class () {
            #[EventHandler('merchant.created', endpointId: 'merchant.notifier')]
            public function notify(string $merchantId): void
            {
                throw new RuntimeException('Notification failed');
            }
        };
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$subscriber::class],
            [$subscriber, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration()],
        );

        try {
            $ecotone->publishEventWithRouting('merchant.created', '1');
            $this->fail('Expected the handler failure to propagate');
        } catch (RuntimeException) {
        }

        $this->assertSame(SpanStatus::Error, self::spanNamed('route merchant.notifier', $recorder)->status);
    }

    public function test_an_asynchronous_event_route_leads_through_the_channel_to_its_handler(): void
    {
        $recorder = new InMemorySpanRecorder();
        $subscriber = new class () {
            #[Asynchronous('async_channel')]
            #[EventHandler('merchant.created', endpointId: 'merchant.notifier')]
            public function notify(string $merchantId): void
            {
            }
        };

        EcotoneLite::bootstrapFlowTesting(
            [$subscriber::class],
            [$subscriber, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration(), SimpleMessageChannelBuilder::createQueueChannel('async_channel')],
        )
            ->publishEventWithRouting('merchant.created', '1')
            ->run('async_channel', ExecutionPollingMetadata::createWithTestingSetup());

        self::getNodeAtTargetedSpan(
            [
                'details' => ['name' => 'send event'],
                'child' => [
                    'details' => ['name' => 'route merchant.notifier'],
                    'child' => [
                        'details' => ['name' => 'send async_channel', 'attributes' => ['messaging.destination.name' => 'async_channel']],
                        'child' => [
                            'details' => ['name' => 'receive async_channel', 'attributes' => ['messaging.destination.name' => 'async_channel']],
                            'child' => ['details' => ['name' => 'process class@anonymous::notify']],
                        ],
                    ],
                ],
            ],
            self::buildTree($recorder->recordedSpans())
        );
    }

    public function test_an_event_driven_projection_is_a_consumer_span_under_its_route(): void
    {
        $recorder = new InMemorySpanRecorder();
        $projection = new #[Projection('orders'), FromStream('orders_stream')] class () {
            #[EventHandler('order.placed')]
            public function whenPlaced(array $event): void
            {
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration()],
        );
        $ecotone->withEvents([Event::createWithType('order.placed', ['orderId' => '1'])]);

        $ecotone->publishEventWithRouting('order.placed', []);

        self::compareTreesByDetails(
            [
                [
                    'details' => ['name' => 'send event'],
                    'children' => [
                        [
                            'details' => ['name' => 'route orders', 'kind' => SpanKind::Producer, 'attributes' => ['ecotone.projection.name' => 'orders']],
                            'children' => [
                                ['details' => ['name' => 'process orders', 'kind' => SpanKind::Consumer, 'attributes' => ['ecotone.projection.name' => 'orders']], 'children' => []],
                            ],
                        ],
                    ],
                ],
            ],
            self::buildTree($recorder->recordedSpans())
        );
    }

    public function test_an_asynchronous_projection_is_a_consumer_span_after_its_channel(): void
    {
        $recorder = new InMemorySpanRecorder();
        $projection = new #[Projection('orders'), FromStream('orders_stream'), Asynchronous('async_channel')] class () {
            #[EventHandler('order.placed')]
            public function whenPlaced(array $event): void
            {
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration(), SimpleMessageChannelBuilder::createQueueChannel('async_channel')],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
        $ecotone->withEvents([Event::createWithType('order.placed', ['orderId' => '1'])]);

        $ecotone->publishEventWithRouting('order.placed', [])
            ->run('async_channel', ExecutionPollingMetadata::createWithTestingSetup());

        self::getNodeAtTargetedSpan(
            [
                'details' => ['name' => 'send event'],
                'child' => [
                    'details' => ['name' => 'route orders'],
                    'child' => [
                        'details' => ['name' => 'send async_channel'],
                        'child' => [
                            'details' => ['name' => 'receive async_channel'],
                            'child' => ['details' => ['name' => 'process orders', 'kind' => SpanKind::Consumer]],
                        ],
                    ],
                ],
            ],
            self::buildTree($recorder->recordedSpans())
        );
    }

    public function test_every_span_names_the_messaging_system_and_handler_spans_name_their_endpoint(): void
    {
        $recorder = new InMemorySpanRecorder();
        $users = new class () {
            #[CommandHandler('user.register', endpointId: 'user.registration')]
            public function register(string $userId): void
            {
            }
        };

        EcotoneLite::bootstrapFlowTesting(
            [$users::class],
            [$users, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration()],
        )
            ->sendCommandWithRouting('user.register', '1');

        foreach ($recorder->recordedSpans() as $span) {
            $this->assertSame('ecotone', $span->attributes['messaging.system'], $span->name);
        }
        $this->assertSame('user.registration', self::spanNamed('process class@anonymous::register', $recorder)->attributes['ecotone.endpoint.id']);
    }

    private static function spanNamed(string $name, InMemorySpanRecorder $recorder): Span
    {
        $spans = array_values(array_filter($recorder->recordedSpans(), fn (Span $span): bool => $span->name === $name));
        self::assertNotEmpty($spans, 'Expected a span named ' . $name . ', got: ' . implode(', ', self::spanNamesWithoutFrameworkNoise($recorder->recordedSpans())));

        return $spans[0];
    }
}
