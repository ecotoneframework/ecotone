<?php

declare(strict_types=1);

namespace Test\Ecotone\Tracing;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Tracing\InMemorySpanRecorder;
use Ecotone\Api\Tracing\Span;
use Ecotone\Api\Tracing\SpanKind;
use Ecotone\Api\Tracing\SpanStatus;
use InvalidArgumentException;
use stdClass;
use Symfony\Component\Uid\Uuid;

/**
 * licence Apache-2.0
 * @internal
 */
final class TracingTreeTest extends TracingTestCase
{
    public function test_tracing_tree_with_single_level_of_nesting(): void
    {
        $recorder = new InMemorySpanRecorder();
        $users = new class () {
            #[CommandHandler('user.register')]
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

        self::compareTreesByDetails(
            [
                [
                    'details' => ['name' => 'send command', 'kind' => SpanKind::Server, 'parent_id' => null],
                    'children' => [
                        [
                            'details' => ['name' => 'process class@anonymous::register', 'kind' => SpanKind::Server],
                            'children' => [],
                        ],
                    ],
                ],
            ],
            self::buildTree($recorder->recordedSpans())
        );
    }

    public function test_tracing_tree_with_two_levels_of_nesting_and_message_handler(): void
    {
        $recorder = new InMemorySpanRecorder();
        $handler = new class () {
            #[CommandHandler('handleCommand', outputChannelName: 'handleMessage')]
            public function handleCommand(Message $message): Message
            {
                return $message;
            }

            #[InternalHandler('handleMessage')]
            public function handle(Message $message): void
            {
            }
        };

        EcotoneLite::bootstrapFlowTesting(
            [$handler::class],
            [$handler, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration()],
        )
            ->sendCommandWithRouting('handleCommand');

        self::compareTreesByDetails(
            [
                [
                    'details' => ['name' => 'send command'],
                    'children' => [
                        ['details' => ['name' => 'process class@anonymous::handleCommand'], 'children' => []],
                        ['details' => ['name' => 'process class@anonymous::handle'], 'children' => []],
                    ],
                ],
            ],
            self::buildTree($recorder->recordedSpans())
        );
    }

    public function test_tracing_tree_with_two_levels_of_nesting(): void
    {
        $recorder = new InMemorySpanRecorder();
        $users = $this->users();
        $subscriber = $this->merchantSubscriber();

        EcotoneLite::bootstrapFlowTesting(
            [$users::class, $subscriber::class],
            [$users, $subscriber, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration()],
        )
            ->publishEventWithRouting('merchant.created', '1');

        self::compareTreesByDetails(
            [
                [
                    'details' => ['name' => 'send event'],
                    'children' => [
                        [
                            'details' => ['name' => 'route merchant.to.user', 'kind' => SpanKind::Producer],
                            'children' => [
                                [
                                    'details' => ['name' => 'process class@anonymous::merchantToUser'],
                                    'children' => [
                                        [
                                            'details' => ['name' => 'send command'],
                                            'children' => [
                                                ['details' => ['name' => 'process class@anonymous::register'], 'children' => []],
                                            ],
                                        ],
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

    public function test_tracing_tree_with_two_branches_on_same_level(): void
    {
        $recorder = new InMemorySpanRecorder();
        $users = $this->users();
        $subscriberOne = $this->merchantSubscriber();
        $subscriberTwo = new class () {
            #[EventHandler('merchant.created', endpointId: 'merchant.to.second.user')]
            public function merchantToUser(string $merchantId, CommandBus $commandBus): void
            {
                $commandBus->sendWithRouting('user.register', $merchantId . '-second');
            }
        };

        EcotoneLite::bootstrapFlowTesting(
            [$users::class, $subscriberOne::class, $subscriberTwo::class],
            [$users, $subscriberOne, $subscriberTwo, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration()],
        )
            ->publishEventWithRouting('merchant.created', '1');

        $commandBusChild = [
            'details' => ['name' => 'send command'],
            'children' => [
                ['details' => ['name' => 'process class@anonymous::register'], 'children' => []],
            ],
        ];
        self::compareTreesByDetails(
            [
                [
                    'details' => ['name' => 'send event'],
                    'children' => [
                        [
                            'details' => ['name' => 'route merchant.to.user'],
                            'children' => [['details' => ['name' => 'process class@anonymous::merchantToUser'], 'children' => [$commandBusChild]]],
                        ],
                        [
                            'details' => ['name' => 'route merchant.to.second.user'],
                            'children' => [['details' => ['name' => 'process class@anonymous::merchantToUser'], 'children' => [$commandBusChild]]],
                        ],
                    ],
                ],
            ],
            self::buildTree($recorder->recordedSpans())
        );
        $this->assertCount(9, self::spanNamesWithoutFrameworkNoise($recorder->recordedSpans()));
    }

    public function test_tracing_tree_with_three_levels_of_nesting(): void
    {
        $recorder = new InMemorySpanRecorder();
        $merchants = new class () {
            #[CommandHandler('merchant.create')]
            public function create(string $merchantId, EventBus $eventBus): void
            {
                $eventBus->publishWithRouting('merchant.created', $merchantId);
            }
        };
        $users = $this->users();
        $subscriber = $this->merchantSubscriber();

        EcotoneLite::bootstrapFlowTesting(
            [$merchants::class, $users::class, $subscriber::class],
            [$merchants, $users, $subscriber, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration()],
        )
            ->sendCommandWithRouting('merchant.create', '1');

        self::compareTreesByDetails(
            [
                [
                    'details' => ['name' => 'send command'],
                    'children' => [
                        [
                            'details' => ['name' => 'process class@anonymous::create'],
                            'children' => [
                                [
                                    'details' => ['name' => 'send event'],
                                    'children' => [
                                        [
                                            'details' => ['name' => 'route merchant.to.user'],
                                            'children' => [
                                                [
                                                    'details' => ['name' => 'process class@anonymous::merchantToUser'],
                                                    'children' => [
                                                        [
                                                            'details' => ['name' => 'send command'],
                                                            'children' => [
                                                                ['details' => ['name' => 'process class@anonymous::register'], 'children' => []],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
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

    public function test_every_span_of_one_flow_shares_the_trace_id_of_its_root(): void
    {
        $recorder = new InMemorySpanRecorder();
        $users = $this->users();
        $subscriber = $this->merchantSubscriber();

        EcotoneLite::bootstrapFlowTesting(
            [$users::class, $subscriber::class],
            [$users, $subscriber, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration()],
        )
            ->publishEventWithRouting('merchant.created', '1');

        $traceIds = array_unique(array_map(fn (Span $span): string => $span->traceId, $recorder->recordedSpans()));
        $this->assertCount(1, $traceIds);
        $this->assertCount(1, array_filter($recorder->recordedSpans(), fn (Span $span): bool => $span->isRoot()));
    }

    public function test_separate_traces(): void
    {
        $recorder = new InMemorySpanRecorder();
        $users = $this->users();

        EcotoneLite::bootstrapFlowTesting(
            [$users::class],
            [$users, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration()],
        )
            ->sendCommandWithRouting('user.register', '1', metadata: ['flowId' => '1'])
            ->sendCommandWithRouting('user.register', '2', metadata: ['flowId' => '2']);

        self::compareTreesByDetails(
            [
                [
                    'details' => ['name' => 'send command', 'attributes' => ['flowId' => '1']],
                    'children' => [
                        ['details' => ['name' => 'process class@anonymous::register', 'attributes' => ['flowId' => '1']], 'children' => []],
                    ],
                ],
                [
                    'details' => ['name' => 'send command', 'attributes' => ['flowId' => '2']],
                    'children' => [
                        ['details' => ['name' => 'process class@anonymous::register', 'attributes' => ['flowId' => '2']], 'children' => []],
                    ],
                ],
            ],
            self::buildTree($recorder->recordedSpans())
        );
        $traceIds = array_unique(array_map(fn (Span $span): string => $span->traceId, $recorder->recordedSpans()));
        $this->assertCount(2, $traceIds);
    }

    public function test_user_land_metadata_is_skipped_when_it_is_not_scalar(): void
    {
        $recorder = new InMemorySpanRecorder();
        $users = $this->users();

        EcotoneLite::bootstrapFlowTesting(
            [$users::class],
            [$users, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration()],
        )
            ->sendCommandWithRouting('user.register', '1', metadata: ['tokens' => ['123'], 'user' => new stdClass(), 'user_id' => '123']);

        $node = self::getNodeAtTargetedSpan(['details' => ['name' => 'send command']], self::buildTree($recorder->recordedSpans()));

        $this->assertArrayNotHasKey('user', $node['details']['attributes']);
        $this->assertArrayNotHasKey('tokens', $node['details']['attributes']);
        $this->assertSame('123', $node['details']['attributes']['user_id']);
    }

    public function test_spans_carry_message_identity_and_a_child_message_points_at_its_parent(): void
    {
        $recorder = new InMemorySpanRecorder();
        $users = $this->users();
        $subscriber = $this->merchantSubscriber();
        $messageId = Uuid::v7()->toRfc4122();
        $correlationId = Uuid::v7()->toRfc4122();
        $timestamp = 1680436648;

        EcotoneLite::bootstrapFlowTesting(
            [$users::class, $subscriber::class],
            [$users, $subscriber, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration()],
        )
            ->publishEventWithRouting('merchant.created', '1', metadata: [
                MessageHeaders::MESSAGE_ID => $messageId,
                MessageHeaders::MESSAGE_CORRELATION_ID => $correlationId,
                MessageHeaders::TIMESTAMP => $timestamp,
            ]);

        $spans = $recorder->recordedSpans();
        $this->assertCount(5, $spans);

        $this->assertChildSpan('process class@anonymous::register', $spans[0], $messageId, $correlationId);
        $this->assertChildSpan('send command', $spans[1], $messageId, $correlationId);
        $this->assertSpanWith('process class@anonymous::merchantToUser', $spans[2], $messageId, $correlationId, $timestamp);
        $this->assertSpanWith('route merchant.to.user', $spans[3], $messageId, $correlationId, $timestamp);
        $this->assertSpanWith('send event', $spans[4], $messageId, $correlationId, $timestamp);
    }

    public function test_a_failing_handler_marks_its_span_and_the_bus_span_as_failed_with_the_exception_recorded(): void
    {
        $recorder = new InMemorySpanRecorder();
        $users = new class () {
            #[CommandHandler('user.register')]
            public function register(string $userId): void
            {
                throw new InvalidArgumentException('User already registered');
            }
        };
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$users::class],
            [$users, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration()],
        );

        try {
            $ecotone->sendCommandWithRouting('user.register', '1');
            $this->fail('Expected the handler failure to propagate');
        } catch (InvalidArgumentException) {
        }

        $spans = $recorder->recordedSpans();
        $this->assertCount(2, $spans);
        foreach ($spans as $span) {
            $this->assertSame(SpanStatus::Error, $span->status);
            $this->assertSame('User already registered', $span->statusDescription);
            $exceptionEvents = array_values(array_filter($span->events, fn ($event): bool => $event->name === 'exception'));
            $this->assertCount(1, $exceptionEvents);
            $this->assertSame(InvalidArgumentException::class, $exceptionEvents[0]->attributes['exception.type']);
            $this->assertSame('User already registered', $exceptionEvents[0]->attributes['exception.message']);
        }
    }

    public function test_query_bus_and_query_handler_are_traced(): void
    {
        $recorder = new InMemorySpanRecorder();
        $users = $this->users();

        EcotoneLite::bootstrapFlowTesting(
            [$users::class],
            [$users, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration()],
        )
            ->sendQueryWithRouting('user.get');

        self::compareTreesByDetails(
            [
                [
                    'details' => ['name' => 'send query'],
                    'children' => [
                        ['details' => ['name' => 'process class@anonymous::isRegistered'], 'children' => []],
                    ],
                ],
            ],
            self::buildTree($recorder->recordedSpans())
        );
    }

    private function users(): object
    {
        return new class () {
            #[CommandHandler('user.register')]
            public function register(string $userId): void
            {
            }

            #[QueryHandler('user.get')]
            public function isRegistered(): bool
            {
                return true;
            }
        };
    }

    private function merchantSubscriber(): object
    {
        return new class () {
            #[EventHandler('merchant.created', endpointId: 'merchant.to.user')]
            public function merchantToUser(string $merchantId, CommandBus $commandBus): void
            {
                $commandBus->sendWithRouting('user.register', $merchantId);
            }
        };
    }

    private function assertSpanWith(string $expectedName, Span $span, string $messageId, string $correlationId, int $timestamp): void
    {
        $this->assertSame($expectedName, $span->name);
        $this->assertSame($messageId, $span->attributes[MessageHeaders::MESSAGE_ID]);
        $this->assertSame($correlationId, $span->attributes[MessageHeaders::MESSAGE_CORRELATION_ID]);
        $this->assertSame($timestamp, $span->attributes[MessageHeaders::TIMESTAMP]);
        $this->assertSame(SpanStatus::Ok, $span->status);
    }

    private function assertChildSpan(string $expectedName, Span $span, string $messageId, string $correlationId): void
    {
        $this->assertSame($expectedName, $span->name);
        $this->assertNotSame($messageId, $span->attributes[MessageHeaders::MESSAGE_ID]);
        $this->assertSame($messageId, $span->attributes[MessageHeaders::PARENT_MESSAGE_ID]);
        $this->assertSame($correlationId, $span->attributes[MessageHeaders::MESSAGE_CORRELATION_ID]);
    }
}
