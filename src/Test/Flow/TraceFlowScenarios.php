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
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Tracing\SpanAttribute;
use Ecotone\Api\Tracing\SpanKind;
use stdClass;
use Symfony\Component\Uid\Uuid;

/**
 * licence Apache-2.0
 */
final class TraceFlowScenarios
{
    private const CARRIED_TRACE_ID = '0af7651916cd43dd8448eb211c80319c';
    private const CARRIED_SPAN_ID = 'b7ad6b7169203331';
    private const TIMESTAMP = 1680436648;

    /**
     * @return list<FlowScenario>
     */
    public static function all(): array
    {
        return [
            self::twoCommandsStartTwoTraces(),
            self::twoAsynchronousCommandsKeepTheirOwnTraces(),
            self::userHeadersTravelIntoEverySpan(),
            self::scheduledPollsStartATraceEach(),
            self::scheduledEndpointWithoutRequestChannelStartsATraceEachPoll(),
            self::scheduledEndpointProducingNothingRecordsNothing(),
            self::logsBecomeEventsOfTheSpanTheyWereWrittenIn(),
            self::commandKeepsTheIdentityItWasSentWith(),
            self::messagesSentFromAHandlerPointAtTheMessageThatCausedThem(),
            self::commandEventCommandChainPointsEachMessageAtItsCause(),
            self::asynchronousConsumerKeepsTheIdentityOfTheMessageItReceived(),
            self::commandCarryingATraceCarrierContinuesThatTrace(),
        ];
    }

    private static function twoCommandsStartTwoTraces(): FlowScenario
    {
        $users = self::users();
        $send = fn (string $flowId) => FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('user.register', $flowId, metadata: [...$metadata, 'flowId' => $flowId]))
            ->expect(
                ExpectedSpan::named('send command')->asRoot()->withAttributes(['flowId' => $flowId])->containing(
                    ExpectedSpan::named('process class@anonymous::register')->withAttributes(['flowId' => $flowId]),
                ),
            )
            ->withNoOtherSpans();

        return new FlowScenario('two commands start two separate traces', [$users::class], [$users], [], [$send('1'), $send('2')]);
    }

    private static function twoAsynchronousCommandsKeepTheirOwnTraces(): FlowScenario
    {
        $users = self::asynchronousUsers();
        $send = fn (string $flowId) => FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('user.register', $flowId, metadata: [...$metadata, 'flowId' => $flowId]))->leavingWorkPending()
            ->expect(
                ExpectedSpan::named('send command')->asRoot()->withAttributes(['flowId' => $flowId])->containing(
                    ExpectedSpan::named('send users')->withAttributes(['flowId' => $flowId]),
                ),
            );
        $consumed = fn (string $flowId) => ExpectedSpan::named('send command')->asRoot()->withAttributes(['flowId' => $flowId])->containing(
            ExpectedSpan::named('send users')->withAttributes(['flowId' => $flowId])->containing(
                ExpectedSpan::named('receive users')->withAttributes(['flowId' => $flowId])->containing(
                    ExpectedSpan::named('process class@anonymous::register')->withAttributes(['flowId' => $flowId]),
                ),
            ),
        );

        return new FlowScenario('two asynchronous commands consumed in one run keep their own traces', [$users::class], [$users], [SimpleMessageChannelBuilder::createQueueChannel('users')], [
            $send('1'),
            $send('2'),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('users', ExecutionPollingMetadata::createWithFinishWhenNoMessages()))
                ->expect($consumed('1'), $consumed('2')),
        ]);
    }

    private static function userHeadersTravelIntoEverySpan(): FlowScenario
    {
        $users = self::asynchronousUsers();
        $carrying = fn (string $name) => ExpectedSpan::named($name)->withAttributes(['user_id' => '123'])->withoutAttributes('tokens', 'user', MessageHeaders::TRACEPARENT);

        return new FlowScenario('user headers travel into every span, non-scalar ones and the trace carrier do not', [$users::class], [$users], [SimpleMessageChannelBuilder::createQueueChannel('users')], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('user.register', '1', metadata: [...$metadata, 'user_id' => '123', 'tokens' => ['123'], 'user' => new stdClass()]))->leavingWorkPending()
                ->expect($carrying('send command')->containing($carrying('send users'))),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('users', ExecutionPollingMetadata::createWithFinishWhenNoMessages()))
                ->expect($carrying('receive users')->containing($carrying('process class@anonymous::register'))),
        ]);
    }

    private static function scheduledPollsStartATraceEach(): FlowScenario
    {
        $workflow = new class () {
            #[Scheduled(requestChannelName: 'add', endpointId: 'scheduled_adding')]
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
        $poll = FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('scheduled_adding', ExecutionPollingMetadata::createWithDefaults()->withExecutionAmountLimit(1)))
            ->expect(
                ExpectedSpan::named('receive add')->asRoot()->ofKind(SpanKind::Consumer)->containing(
                    ExpectedSpan::named('process class@anonymous::add')->withAttributes([SpanAttribute::ENDPOINT_ID => 'adding']),
                ),
            )
            ->withNoOtherSpans();

        return new FlowScenario('scheduled endpoint starts a trace on every poll', [$workflow::class], [$workflow], [], [$poll, $poll]);
    }

    private static function scheduledEndpointWithoutRequestChannelStartsATraceEachPoll(): FlowScenario
    {
        $scheduled = new class () {
            #[Scheduled(endpointId: 'scheduled_handler')]
            #[Poller(fixedRateInMilliseconds: 1)]
            public function handle(): void
            {
            }
        };
        $poll = FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('scheduled_handler', ExecutionPollingMetadata::createWithDefaults()->withExecutionAmountLimit(1)))
            ->expect(ExpectedSpan::named('poll scheduled_handler')->asRoot()->ofKind(SpanKind::Consumer))
            ->withNoOtherSpans();

        return new FlowScenario('scheduled endpoint without a request channel starts a trace on every poll', [$scheduled::class], [$scheduled], [], [$poll, $poll]);
    }

    private static function scheduledEndpointProducingNothingRecordsNothing(): FlowScenario
    {
        $workflow = new class () {
            #[Scheduled(requestChannelName: 'add', endpointId: 'scheduled_nothing')]
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

        return new FlowScenario('scheduled endpoint producing nothing records nothing', [$workflow::class], [$workflow], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('scheduled_nothing', ExecutionPollingMetadata::createWithDefaults()->withExecutionAmountLimit(1)))
                ->withNoOtherSpans(),
        ]);
    }

    private static function logsBecomeEventsOfTheSpanTheyWereWrittenIn(): FlowScenario
    {
        $users = new class () {
            #[Asynchronous('users')]
            #[CommandHandler('user.register', endpointId: 'user.registration')]
            public function register(string $userId, EventBus $eventBus): void
            {
                $eventBus->publishWithRouting('user.registered', $userId);
            }
        };
        $notifier = new class () {
            #[Asynchronous('users')]
            #[EventHandler('user.registered', endpointId: 'user.notifying')]
            public function notify(string $userId): void
            {
            }
        };

        return new FlowScenario('logs become events of the span they were written in', [$users::class, $notifier::class], [$users, $notifier], [SimpleMessageChannelBuilder::createQueueChannel('users')], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('user.register', '1', metadata: $metadata))->leavingWorkPending(),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('users', ExecutionPollingMetadata::createWithFinishWhenNoMessages()))
                ->expect(
                    ExpectedSpan::named('receive users')->containing(
                        ExpectedSpan::named('process class@anonymous::register')->containing(
                            ExpectedSpan::named('send event')->withEventStartingWith('Publishing Event Message')->containing(
                                ExpectedSpan::named('route user.notifying')->withEventStartingWith('Collecting message with id'),
                            ),
                        ),
                    ),
                ),
        ]);
    }

    private static function commandKeepsTheIdentityItWasSentWith(): FlowScenario
    {
        $users = self::users();
        [$messageId, $correlationId] = [Uuid::v7()->toRfc4122(), Uuid::v7()->toRfc4122()];
        $identified = fn (string $name) => ExpectedSpan::named($name)->withAttributes([
            MessageHeaders::MESSAGE_ID => $messageId,
            MessageHeaders::MESSAGE_CORRELATION_ID => $correlationId,
            MessageHeaders::TIMESTAMP => self::TIMESTAMP,
        ]);

        return new FlowScenario('command keeps the message identity it was sent with', [$users::class], [$users], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('user.register', '1', metadata: [...$metadata, ...self::identity($messageId, $correlationId)]))
                ->expect($identified('send command')->containing($identified('process class@anonymous::register')))
                ->withNoOtherSpans(),
        ]);
    }

    private static function messagesSentFromAHandlerPointAtTheMessageThatCausedThem(): FlowScenario
    {
        $users = self::users();
        $subscriber = self::merchantSubscriber();
        [$messageId, $correlationId] = [Uuid::v7()->toRfc4122(), Uuid::v7()->toRfc4122()];
        $original = fn (string $name) => ExpectedSpan::named($name)->withAttributes([MessageHeaders::MESSAGE_ID => $messageId, MessageHeaders::MESSAGE_CORRELATION_ID => $correlationId, MessageHeaders::TIMESTAMP => self::TIMESTAMP]);
        $caused = fn (string $name) => ExpectedSpan::named($name)->withAttributes([MessageHeaders::PARENT_MESSAGE_ID => $messageId, MessageHeaders::MESSAGE_CORRELATION_ID => $correlationId])->withAttributeOtherThan(MessageHeaders::MESSAGE_ID, $messageId);

        return new FlowScenario('messages sent from a handler point at the message that caused them', [$users::class, $subscriber::class], [$users, $subscriber], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->publishEventWithRouting('merchant.created', '1', metadata: [...$metadata, ...self::identity($messageId, $correlationId)]))
                ->expect(
                    $original('send event')->containing(
                        $original('route merchant.to.user')->containing(
                            $original('process class@anonymous::merchantToUser')->containing(
                                $caused('send command')->containing($caused('process class@anonymous::register')),
                            ),
                        ),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function commandEventCommandChainPointsEachMessageAtItsCause(): FlowScenario
    {
        $merchants = new class () {
            #[CommandHandler('merchant.create', endpointId: 'merchant.creation')]
            public function create(string $merchantId, EventBus $eventBus): void
            {
                $eventBus->publishWithRouting('merchant.created', $merchantId);
            }
        };
        $users = self::users();
        $subscriber = self::merchantSubscriber();
        [$messageId, $correlationId] = [Uuid::v7()->toRfc4122(), Uuid::v7()->toRfc4122()];
        $original = fn (string $name) => ExpectedSpan::named($name)->withAttributes([MessageHeaders::MESSAGE_ID => $messageId, MessageHeaders::MESSAGE_CORRELATION_ID => $correlationId, MessageHeaders::TIMESTAMP => self::TIMESTAMP]);
        $causedByOriginal = fn (string $name) => ExpectedSpan::named($name)->withAttributes([MessageHeaders::PARENT_MESSAGE_ID => $messageId, MessageHeaders::MESSAGE_CORRELATION_ID => $correlationId])->withAttributeOtherThan(MessageHeaders::MESSAGE_ID, $messageId);

        return new FlowScenario('command, event and command each point at the message that caused them', [$merchants::class, $users::class, $subscriber::class], [$merchants, $users, $subscriber], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('merchant.create', '1', metadata: [...$metadata, ...self::identity($messageId, $correlationId)]))
                ->expect(
                    $original('send command')->containing(
                        $original('process class@anonymous::create')->containing(
                            $causedByOriginal('send event')->containing(
                                $causedByOriginal('route merchant.to.user')->containing(
                                    $causedByOriginal('process class@anonymous::merchantToUser')->containing(
                                        ExpectedSpan::named('send command')
                                            ->withAttributeEqualToParents(MessageHeaders::PARENT_MESSAGE_ID, MessageHeaders::MESSAGE_ID)
                                            ->withAttributes([MessageHeaders::MESSAGE_CORRELATION_ID => $correlationId])
                                            ->containing(
                                                ExpectedSpan::named('process class@anonymous::register')
                                                    ->withAttributeEqualToParents(MessageHeaders::MESSAGE_ID)
                                                    ->withAttributes([MessageHeaders::MESSAGE_CORRELATION_ID => $correlationId]),
                                            ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function asynchronousConsumerKeepsTheIdentityOfTheMessageItReceived(): FlowScenario
    {
        $users = self::asynchronousUsers();
        [$messageId, $correlationId] = [Uuid::v7()->toRfc4122(), Uuid::v7()->toRfc4122()];
        $identified = fn (string $name) => ExpectedSpan::named($name)->withAttributes([MessageHeaders::MESSAGE_ID => $messageId, MessageHeaders::MESSAGE_CORRELATION_ID => $correlationId]);

        return new FlowScenario('asynchronous consumer keeps the identity of the message it received', [$users::class], [$users], [SimpleMessageChannelBuilder::createQueueChannel('users')], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('user.register', '1', metadata: [...$metadata, ...self::identity($messageId, $correlationId)]))->leavingWorkPending()
                ->expect($identified('send command')->containing(ExpectedSpan::named('send users'))),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('users', ExecutionPollingMetadata::createWithFinishWhenNoMessages()))
                ->expect($identified('receive users')->containing($identified('process class@anonymous::register'))),
        ]);
    }

    private static function commandCarryingATraceCarrierContinuesThatTrace(): FlowScenario
    {
        $users = self::users();

        return new FlowScenario('command carrying a trace carrier continues that trace', [$users::class], [$users], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('user.register', '1', metadata: [...$metadata, MessageHeaders::TRACEPARENT => '00-' . self::CARRIED_TRACE_ID . '-' . self::CARRIED_SPAN_ID . '-01']))
                ->expect(
                    ExpectedSpan::named('send command')->inTrace(self::CARRIED_TRACE_ID)->withParentSpanId(self::CARRIED_SPAN_ID)->containing(
                        ExpectedSpan::named('process class@anonymous::register')->inTrace(self::CARRIED_TRACE_ID),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    /**
     * @return array<string, string|int>
     */
    private static function identity(string $messageId, string $correlationId): array
    {
        return [MessageHeaders::MESSAGE_ID => $messageId, MessageHeaders::MESSAGE_CORRELATION_ID => $correlationId, MessageHeaders::TIMESTAMP => self::TIMESTAMP];
    }

    private static function users(): object
    {
        return new class () {
            #[CommandHandler('user.register', endpointId: 'user.registration')]
            public function register(string $userId): void
            {
            }
        };
    }

    private static function asynchronousUsers(): object
    {
        return new class () {
            #[Asynchronous('users')]
            #[CommandHandler('user.register', endpointId: 'user.registration')]
            public function register(string $userId): void
            {
            }
        };
    }

    private static function merchantSubscriber(): object
    {
        return new class () {
            #[EventHandler('merchant.created', endpointId: 'merchant.to.user')]
            public function merchantToUser(string $merchantId, CommandBus $commandBus): void
            {
                $commandBus->sendWithRouting('user.register', $merchantId);
            }
        };
    }
}
