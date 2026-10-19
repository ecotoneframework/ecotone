<?php

declare(strict_types=1);

namespace Test\Ecotone\Tracing;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\Poller;
use Ecotone\Api\Attribute\Scheduled;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Tracing\InMemorySpanRecorder;
use Ecotone\Api\Tracing\Span;
use Ecotone\Api\Tracing\SpanKind;
use Symfony\Component\Uid\Uuid;

/**
 * licence Apache-2.0
 * @internal
 */
final class AsynchronousTracingTest extends TracingTestCase
{
    public function test_tracing_with_asynchronous_handler(): void
    {
        $recorder = new InMemorySpanRecorder();
        $users = $this->asynchronousUsers();

        EcotoneLite::bootstrapFlowTesting(
            [$users::class],
            [$users, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration(), SimpleMessageChannelBuilder::createQueueChannel('async_channel')],
        )
            ->sendCommandWithRouting('user.register', '1')
            ->run('async_channel', ExecutionPollingMetadata::createWithTestingSetup());

        self::compareTreesByDetails(
            [
                [
                    'details' => ['name' => 'send command'],
                    'children' => [
                        [
                            'details' => ['name' => 'send async_channel', 'kind' => SpanKind::Producer],
                            'children' => [
                                [
                                    'details' => ['name' => 'receive async_channel', 'kind' => SpanKind::Consumer],
                                    'children' => [
                                        ['details' => ['name' => 'process class@anonymous::register'], 'children' => []],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            self::buildTree($recorder->recordedSpans())
        );
    }

    public function test_the_consumer_continues_the_trace_of_the_sender_across_the_queue(): void
    {
        $recorder = new InMemorySpanRecorder();
        $users = $this->asynchronousUsers();

        EcotoneLite::bootstrapFlowTesting(
            [$users::class],
            [$users, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration(), SimpleMessageChannelBuilder::createQueueChannel('async_channel')],
        )
            ->sendCommandWithRouting('user.register', '1')
            ->run('async_channel', ExecutionPollingMetadata::createWithTestingSetup());

        $traceIds = array_unique(array_map(fn (Span $span): string => $span->traceId, $recorder->recordedSpans()));
        $this->assertCount(1, $traceIds);
    }

    public function test_tracing_same_message_for_asynchronous_scenario(): void
    {
        $recorder = new InMemorySpanRecorder();
        $users = $this->asynchronousUsers();
        $messageId = Uuid::v7()->toRfc4122();
        $correlationId = Uuid::v7()->toRfc4122();

        EcotoneLite::bootstrapFlowTesting(
            [$users::class],
            [$users, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration(), SimpleMessageChannelBuilder::createQueueChannel('async_channel')],
        )
            ->sendCommandWithRouting('user.register', '1', metadata: [
                MessageHeaders::MESSAGE_ID => $messageId,
                MessageHeaders::MESSAGE_CORRELATION_ID => $correlationId,
            ])
            ->run('async_channel', ExecutionPollingMetadata::createWithTestingSetup());

        $node = self::getNodeAtTargetedSpan(
            [
                'details' => ['name' => 'send command'],
                'child' => [
                    'details' => ['name' => 'send async_channel'],
                    'child' => ['details' => ['name' => 'receive async_channel']],
                ],
            ],
            self::buildTree($recorder->recordedSpans())
        );

        $this->assertSame($correlationId, $node['details']['attributes'][MessageHeaders::MESSAGE_CORRELATION_ID]);
        $this->assertSame($messageId, $node['details']['attributes'][MessageHeaders::MESSAGE_ID]);
        $child = $node['children'][array_key_first($node['children'])];
        $this->assertSame($correlationId, $child['details']['attributes'][MessageHeaders::MESSAGE_CORRELATION_ID]);
        $this->assertSame($messageId, $child['details']['attributes'][MessageHeaders::MESSAGE_ID]);
    }

    public function test_two_traces_with_asynchronous_handlers(): void
    {
        $recorder = new InMemorySpanRecorder();
        $users = $this->asynchronousUsers();

        EcotoneLite::bootstrapFlowTesting(
            [$users::class],
            [$users, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration(), SimpleMessageChannelBuilder::createQueueChannel('async_channel')],
        )
            ->sendCommandWithRouting('user.register', '1', metadata: ['flowId' => '1'])
            ->sendCommandWithRouting('user.register', '2', metadata: ['flowId' => '2'])
            ->run('async_channel', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 2));

        $expectedFlow = fn (string $flowId): array => [
            'details' => ['name' => 'send command', 'attributes' => ['flowId' => $flowId]],
            'children' => [
                [
                    'details' => ['name' => 'send async_channel', 'attributes' => ['flowId' => $flowId]],
                    'children' => [
                        [
                            'details' => ['name' => 'receive async_channel', 'attributes' => ['flowId' => $flowId]],
                            'children' => [
                                ['details' => ['name' => 'process class@anonymous::register', 'attributes' => ['flowId' => $flowId]], 'children' => []],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        self::compareTreesByDetails([$expectedFlow('1'), $expectedFlow('2')], self::buildTree($recorder->recordedSpans()));
        $this->assertCount(2, array_unique(array_map(fn (Span $span): string => $span->traceId, $recorder->recordedSpans())));
    }

    public function test_user_headers_travel_with_the_message_into_every_span(): void
    {
        $recorder = new InMemorySpanRecorder();
        $users = $this->asynchronousUsers();

        EcotoneLite::bootstrapFlowTesting(
            [$users::class],
            [$users, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration(), SimpleMessageChannelBuilder::createQueueChannel('async_channel')],
        )
            ->sendCommandWithRouting('user.register', '1', metadata: ['user_id' => '123'])
            ->run('async_channel', ExecutionPollingMetadata::createWithTestingSetup());

        foreach (['send command', 'send async_channel', 'receive async_channel', 'process class@anonymous::register'] as $spanName) {
            $spans = array_values(array_filter($recorder->recordedSpans(), fn (Span $span): bool => $span->name === $spanName));
            $this->assertCount(1, $spans, $spanName);
            $this->assertSame('123', $spans[0]->attributes['user_id'], $spanName);
        }
    }

    public function test_the_trace_carrier_header_is_not_reported_as_a_user_attribute(): void
    {
        $recorder = new InMemorySpanRecorder();
        $users = $this->asynchronousUsers();

        EcotoneLite::bootstrapFlowTesting(
            [$users::class],
            [$users, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration(), SimpleMessageChannelBuilder::createQueueChannel('async_channel')],
        )
            ->sendCommandWithRouting('user.register', '1')
            ->run('async_channel', ExecutionPollingMetadata::createWithTestingSetup());

        foreach ($recorder->recordedSpans() as $span) {
            $this->assertArrayNotHasKey(MessageHeaders::TRACEPARENT, $span->attributes, $span->name);
        }
    }

    public function test_an_event_collected_during_the_asynchronous_handler_is_sent_under_the_consumer_span_in_the_same_trace(): void
    {
        $recorder = new InMemorySpanRecorder();
        $users = new class () {
            #[Asynchronous('async_channel')]
            #[CommandHandler('user.register', endpointId: 'user.register.endpoint')]
            public function register(string $userId, EventBus $eventBus): void
            {
                $eventBus->publishWithRouting('user.registered', $userId);
            }
        };
        $notifier = new class () {
            #[Asynchronous('async_channel')]
            #[EventHandler('user.registered', endpointId: 'user.registered.endpoint')]
            public function notify(string $userId): void
            {
            }
        };

        EcotoneLite::bootstrapFlowTesting(
            [$users::class, $notifier::class],
            [$users, $notifier, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration(), SimpleMessageChannelBuilder::createQueueChannel('async_channel')],
        )
            ->sendCommandWithRouting('user.register', '1')
            ->run('async_channel', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 2));

        self::getNodeAtTargetedSpan(
            [
                'details' => ['name' => 'send command'],
                'child' => [
                    'details' => ['name' => 'send async_channel'],
                    'child' => [
                        'details' => ['name' => 'receive async_channel'],
                        'child' => [
                            'details' => ['name' => 'send async_channel'],
                            'child' => [
                                'details' => ['name' => 'receive async_channel'],
                                'child' => ['details' => ['name' => 'process class@anonymous::notify']],
                            ],
                        ],
                    ],
                ],
            ],
            self::buildTree($recorder->recordedSpans())
        );
        self::getNodeAtTargetedSpan(
            [
                'details' => ['name' => 'send command'],
                'child' => [
                    'details' => ['name' => 'send async_channel'],
                    'child' => [
                        'details' => ['name' => 'receive async_channel'],
                        'child' => [
                            'details' => ['name' => 'process class@anonymous::register'],
                            'child' => ['details' => ['name' => 'send event']],
                        ],
                    ],
                ],
            ],
            self::buildTree($recorder->recordedSpans())
        );
        $this->assertCount(1, array_unique(array_map(fn (Span $span): string => $span->traceId, $recorder->recordedSpans())));
    }

    public function test_adding_logs_as_events_in_span(): void
    {
        $recorder = new InMemorySpanRecorder();
        $users = new class () {
            #[Asynchronous('async_channel')]
            #[CommandHandler('user.register', endpointId: 'user.register.endpoint')]
            public function register(string $userId, EventBus $eventBus): void
            {
                $eventBus->publishWithRouting('user.registered', $userId);
            }
        };
        $notifier = new class () {
            #[Asynchronous('async_channel')]
            #[EventHandler('user.registered', endpointId: 'user.registered.endpoint')]
            public function notify(string $userId): void
            {
            }
        };

        EcotoneLite::bootstrapFlowTesting(
            [$users::class, $notifier::class],
            [$users, $notifier, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration(), SimpleMessageChannelBuilder::createQueueChannel('async_channel')],
        )
            ->sendCommandWithRouting('user.register', '1')
            ->run('async_channel');

        $eventBus = self::getNodeAtTargetedSpan(
            [
                'details' => ['name' => 'send command'],
                'child' => [
                    'details' => ['name' => 'send async_channel'],
                    'child' => [
                        'details' => ['name' => 'receive async_channel'],
                        'child' => [
                            'details' => ['name' => 'process class@anonymous::register'],
                            'child' => ['details' => ['name' => 'send event']],
                        ],
                    ],
                ],
            ],
            self::buildTree($recorder->recordedSpans())
        );

        $eventBusEvents = array_map(fn ($event): string => $event->name, $eventBus['details']['events']);
        $this->assertNotEmpty(array_filter($eventBusEvents, fn (string $name): bool => str_starts_with($name, 'Publishing Event Message')));

        $route = $eventBus['children'][array_key_first($eventBus['children'])];
        $this->assertSame('route user.registered.endpoint', $route['details']['name']);
        $routeEvents = array_map(fn ($event): string => $event->name, $route['details']['events']);
        $this->assertNotEmpty(array_filter($routeEvents, fn (string $name): bool => str_starts_with($name, 'Collecting message with id')));
    }

    public function test_tracing_scheduled_handler(): void
    {
        $recorder = new InMemorySpanRecorder();
        $scheduled = new class () {
            #[Scheduled(endpointId: 'scheduled_handler')]
            #[Poller(fixedRateInMilliseconds: 1)]
            public function handle(): void
            {
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$scheduled::class],
            [$scheduled, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration()],
        );

        $ecotone->run('scheduled_handler', ExecutionPollingMetadata::createWithDefaults()->withExecutionAmountLimit(1));
        $ecotone->run('scheduled_handler', ExecutionPollingMetadata::createWithDefaults()->withExecutionAmountLimit(1));

        $polls = array_values(array_filter($recorder->recordedSpans(), fn (Span $span): bool => $span->name === 'receive nullChannel'));
        $this->assertCount(2, $polls);
        foreach ($polls as $poll) {
            $this->assertSame(SpanKind::Consumer, $poll->kind);
            $this->assertTrue($poll->isRoot());
        }
        $this->assertNotSame($polls[0]->traceId, $polls[1]->traceId);
    }

    public function test_tracing_workflow_scheduled_handler(): void
    {
        $recorder = new InMemorySpanRecorder();
        $workflow = $this->workflowScheduledHandler();

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$workflow::class],
            [$workflow, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration()],
        );

        $ecotone->run('scheduled_handler', ExecutionPollingMetadata::createWithDefaults()->withExecutionAmountLimit(1));
        $ecotone->run('scheduled_handler', ExecutionPollingMetadata::createWithDefaults()->withExecutionAmountLimit(1));

        $spans = $recorder->recordedSpans();
        $polls = array_values(array_filter($spans, fn (Span $span): bool => $span->name === 'receive add'));
        $this->assertCount(2, $polls);
        foreach ($polls as $poll) {
            $this->assertTrue($poll->isRoot());
            $handlers = array_values(array_filter($spans, fn (Span $span): bool => $span->parentSpanId === $poll->spanId));
            $this->assertCount(1, $handlers);
            $this->assertMatchesRegularExpression('/^process class@anonymous\S*::add$/', $handlers[0]->name);
            $this->assertSame($poll->traceId, $handlers[0]->traceId);
        }
        $this->assertNotSame($polls[0]->traceId, $polls[1]->traceId);
    }

    public function test_no_tracing_when_there_is_nothing_to_trace(): void
    {
        $recorder = new InMemorySpanRecorder();
        $workflow = $this->workflowScheduledHandler();

        EcotoneLite::bootstrapFlowTesting(
            [$workflow::class],
            [$workflow, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration()],
        )
            ->run('scheduled_handler_without_result', ExecutionPollingMetadata::createWithDefaults()->withExecutionAmountLimit(1));

        $this->assertSame([], $recorder->recordedSpans());
    }

    private function asynchronousUsers(): object
    {
        return new class () {
            #[Asynchronous('async_channel')]
            #[CommandHandler('user.register', endpointId: 'user.register.endpoint')]
            public function register(string $userId): void
            {
            }
        };
    }

    private function workflowScheduledHandler(): object
    {
        return new class () {
            #[Scheduled(requestChannelName: 'add', endpointId: 'scheduled_handler')]
            #[Poller(fixedRateInMilliseconds: 1)]
            public function produce(): int
            {
                return 1;
            }

            #[Scheduled(requestChannelName: 'add', endpointId: 'scheduled_handler_without_result')]
            #[Poller(fixedRateInMilliseconds: 1)]
            public function produceNothing(): ?int
            {
                return null;
            }

            #[InternalHandler(inputChannelName: 'add')]
            public function add(): void
            {
            }
        };
    }
}
