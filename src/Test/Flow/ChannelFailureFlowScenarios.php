<?php

declare(strict_types=1);

namespace Ecotone\Test\Flow;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Presend;
use Ecotone\Api\ExtensionObject\DistributedServiceMap;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\RetryTemplateBuilder;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageChannel;
use Ecotone\Api\Tracing\SpanAttribute;
use Ecotone\Api\Tracing\SpanKind;
use Ecotone\Api\Tracing\SpanStatus;
use Ecotone\Messaging\Channel\AbstractChannelInterceptor;
use Ecotone\Messaging\Channel\ExceptionalQueueChannel;
use Ecotone\Messaging\Channel\PollableChannel\PollableChannelConfiguration;
use Ecotone\Messaging\Channel\SimpleChannelInterceptorBuilder;
use Ecotone\Test\StaticMessageBuilder;
use RuntimeException;
use Throwable;

/**
 * licence Apache-2.0
 */
final class ChannelFailureFlowScenarios
{
    /**
     * @return list<FlowScenario>
     */
    public static function all(): array
    {
        return [
            self::forwarded('event forwarded to a channel whose send fails', [ExceptionalQueueChannel::createWithExceptionOnSend('notifications')], [], 'Exception on send'),
            self::forwarded('event forwarded to a channel whose interceptor fails before the send', [
                SimpleMessageChannelBuilder::createQueueChannel('notifications'),
                SimpleChannelInterceptorBuilder::create('notifications', 'failingBeforeSend'),
            ], ['failingBeforeSend' => new class () extends AbstractChannelInterceptor {
                public function preSend(Message $message, MessageChannel $messageChannel): ?Message
                {
                    throw new RuntimeException('Exception before send');
                }
            }], 'Exception before send', null),
            self::filteredOutBeforeTheSend(),
            self::forwarded('event forwarded to a failing channel whose other interceptor fails during cleanup', [
                ExceptionalQueueChannel::createWithExceptionOnSend('notifications'),
                SimpleChannelInterceptorBuilder::create('notifications', 'failingCleanup')->withPrecedence(500),
            ], ['failingCleanup' => new class () extends AbstractChannelInterceptor {
                public function afterSendCompletion(Message $message, MessageChannel $messageChannel, ?Throwable $exception): bool
                {
                    if ($exception !== null) {
                        throw new RuntimeException('Exception during cleanup');
                    }

                    return false;
                }
            }], 'Exception'),
            self::forwarded('event forwarded to a channel whose other interceptor fails after the send', [
                SimpleMessageChannelBuilder::createQueueChannel('notifications'),
                SimpleChannelInterceptorBuilder::create('notifications', 'failingAfterSend')->withPrecedence(500),
            ], ['failingAfterSend' => new class () extends AbstractChannelInterceptor {
                public function postSend(Message $message, MessageChannel $messageChannel): void
                {
                    throw new RuntimeException('Exception after send');
                }
            }], 'Exception after send', SpanStatus::Ok),
            self::rebuiltWithoutFrameworkHeadersBeforeTheSend(),
            self::presendInterceptorFailingBeforeTheAsynchronousSend(),
            self::failingHandlerWhoseErrorChannelSendFails(),
            self::distributedSendFailing(),
            self::sendSucceedingOnRetry(),
            self::sendFailingIntoTheChannelErrorChannel(),
        ];
    }

    /**
     * @param list<object> $channels
     * @param array<string, object> $interceptors
     */
    private static function forwarded(string $name, array $channels, array $interceptors, string $expectedFailure, ?SpanStatus $notificationsSend = SpanStatus::Error): FlowScenario
    {
        [$users, $notifier] = self::registrationForwardedToNotifications();
        $registered = ExpectedSpan::named('process class@anonymous::register')->withStatus(SpanStatus::Ok)->withAttributes([SpanAttribute::ENDPOINT_ID => 'user.registration'])->containing(
            ExpectedSpan::named('send event')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'user.registered'])->containing(
                ExpectedSpan::named('route user.notifying')->ofKind(SpanKind::Producer),
            ),
        );
        $consume = FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('users', ExecutionPollingMetadata::createWithTestingSetup()))
            ->expect(
                $notificationsSend === null
                    ? ExpectedSpan::named('receive users')->ofKind(SpanKind::Consumer)->withStatus(SpanStatus::Error)->containing($registered)
                    : ExpectedSpan::named('receive users')->ofKind(SpanKind::Consumer)->withStatus(SpanStatus::Error)->containing(
                        $registered,
                        ExpectedSpan::named('send notifications')->ofKind(SpanKind::Producer)->withStatus($notificationsSend),
                    ),
            )
            ->expectNoSpanNamed('process class@anonymous::notify', ...($notificationsSend === null ? ['send notifications'] : []));

        return new FlowScenario($name, [$users::class, $notifier::class], [$users, $notifier, ...$interceptors], [SimpleMessageChannelBuilder::createQueueChannel('users'), ...$channels], [
            self::registrationSentToTheQueue(),
            $consume->failingWith($expectedFailure),
        ]);
    }

    private static function filteredOutBeforeTheSend(): FlowScenario
    {
        [$users, $notifier] = self::registrationForwardedToNotifications();
        $filteringOut = new class () extends AbstractChannelInterceptor {
            public function preSend(Message $message, MessageChannel $messageChannel): ?Message
            {
                return null;
            }
        };

        return new FlowScenario('event forwarded to a channel whose interceptor filters it out is not sent', [$users::class, $notifier::class], [$users, $notifier, 'filteringOut' => $filteringOut], [
            SimpleMessageChannelBuilder::createQueueChannel('users'),
            SimpleMessageChannelBuilder::createQueueChannel('notifications'),
            SimpleChannelInterceptorBuilder::create('notifications', 'filteringOut'),
        ], [
            self::registrationSentToTheQueue(),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('users', ExecutionPollingMetadata::createWithTestingSetup()))
                ->expect(
                    ExpectedSpan::named('receive users')->withStatus(SpanStatus::Ok)->containing(
                        ExpectedSpan::named('process class@anonymous::register')->containing(
                            ExpectedSpan::named('send event')->containing(ExpectedSpan::named('route user.notifying')),
                        ),
                    ),
                )
                ->expectNoSpanNamed('send notifications')
                ->withNoOtherSpans(),
        ]);
    }

    private static function rebuiltWithoutFrameworkHeadersBeforeTheSend(): FlowScenario
    {
        [$users, $notifier] = self::registrationForwardedToNotifications();
        $rebuilding = new class () extends AbstractChannelInterceptor {
            public function preSend(Message $message, MessageChannel $messageChannel): ?Message
            {
                return StaticMessageBuilder::withPayload($message->getPayload())->build();
            }
        };

        return new FlowScenario('event forwarded to a channel whose interceptor rebuilds the message without framework headers keeps its trace', [$users::class, $notifier::class], [$users, $notifier, 'rebuilding' => $rebuilding], [
            SimpleMessageChannelBuilder::createQueueChannel('users'),
            SimpleMessageChannelBuilder::createQueueChannel('notifications'),
            SimpleChannelInterceptorBuilder::create('notifications', 'rebuilding'),
        ], [
            self::registrationSentToTheQueue(),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('users', ExecutionPollingMetadata::createWithTestingSetup()))
                ->expect(
                    ExpectedSpan::named('receive users')->withStatus(SpanStatus::Ok)->containing(
                        ExpectedSpan::named('send notifications')->ofKind(SpanKind::Producer)->withStatus(SpanStatus::Ok),
                    ),
                )
                ->leavingWorkPending(),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('notifications', ExecutionPollingMetadata::createWithTestingSetup()))
                ->expect(
                    ExpectedSpan::named('send notifications')->containing(
                        ExpectedSpan::named('receive notifications')->ofKind(SpanKind::Consumer),
                    ),
                )
                ->expectNoSpanNamed('process class@anonymous::notify'),
        ]);
    }

    private static function sendSucceedingOnRetry(): FlowScenario
    {
        [$users, $notifier] = self::registrationForwardedToNotifications();

        return new FlowScenario('event forwarded to a channel whose send succeeds on retry', [$users::class, $notifier::class], [$users, $notifier], [
            SimpleMessageChannelBuilder::createQueueChannel('users'),
            ExceptionalQueueChannel::createWithExceptionOnSend('notifications', 1),
            PollableChannelConfiguration::create('notifications', RetryTemplateBuilder::fixedBackOff(0)->maxRetries(1)->build()),
        ], [
            self::registrationSentToTheQueue(),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('users', ExecutionPollingMetadata::createWithTestingSetup()))
                ->expect(
                    ExpectedSpan::named('receive users')->withStatus(SpanStatus::Ok)->containing(
                        ExpectedSpan::named('send notifications')->ofKind(SpanKind::Producer)->withStatus(SpanStatus::Ok),
                    ),
                )
                ->leavingWorkPending(),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('notifications', ExecutionPollingMetadata::createWithTestingSetup()))
                ->expect(
                    ExpectedSpan::named('send notifications')->withStatus(SpanStatus::Ok)->containing(
                        ExpectedSpan::named('receive notifications')->containing(
                            ExpectedSpan::named('process class@anonymous::notify')->withAttributes([SpanAttribute::ENDPOINT_ID => 'user.notifying']),
                        ),
                    ),
                ),
        ]);
    }

    private static function sendFailingIntoTheChannelErrorChannel(): FlowScenario
    {
        [$users, $notifier] = self::registrationForwardedToNotifications();

        return new FlowScenario('event forwarded to a channel whose send fails into the channel error channel', [$users::class, $notifier::class], [$users, $notifier], [
            SimpleMessageChannelBuilder::createQueueChannel('users'),
            SimpleMessageChannelBuilder::createQueueChannel('notifications_failed_sends'),
            ExceptionalQueueChannel::createWithExceptionOnSend('notifications'),
            PollableChannelConfiguration::neverRetry('notifications')->withErrorChannel('notifications_failed_sends'),
        ], [
            self::registrationSentToTheQueue(),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('users', ExecutionPollingMetadata::createWithTestingSetup()))
                ->expect(
                    ExpectedSpan::named('receive users')->withStatus(SpanStatus::Ok)->containing(
                        ExpectedSpan::named('send notifications')->ofKind(SpanKind::Producer)->withStatus(SpanStatus::Error, 'Exception on send'),
                    ),
                )
                ->expectNoSpanNamed('process class@anonymous::notify')
                ->leavingWorkPending(),
        ]);
    }

    /**
     * @return array{object, object}
     */
    private static function registrationForwardedToNotifications(): array
    {
        return [
            new class () {
                #[Asynchronous('users')]
                #[CommandHandler('user.register', endpointId: 'user.registration')]
                public function register(string $userId, EventBus $eventBus): void
                {
                    $eventBus->publishWithRouting('user.registered', $userId);
                }
            },
            new class () {
                #[Asynchronous('notifications')]
                #[EventHandler('user.registered', endpointId: 'user.notifying')]
                public function notify(string $userId): void
                {
                }
            },
        ];
    }

    private static function registrationSentToTheQueue(): FlowStage
    {
        return FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('user.register', '1', metadata: $metadata))->leavingWorkPending()
            ->expect(
                ExpectedSpan::named('send command')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'user.register'])->containing(
                    ExpectedSpan::named('send users')->ofKind(SpanKind::Producer),
                ),
            )
            ->withNoOtherSpans();
    }

    private static function presendInterceptorFailingBeforeTheAsynchronousSend(): FlowScenario
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
            #[Asynchronous('notifications')]
            #[EventHandler('user.registered', endpointId: 'user.notifying')]
            public function notify(string $userId): void
            {
            }
        };
        $blocking = new class () {
            #[Presend(pointcut: EventHandler::class)]
            public function block(): void
            {
                throw new RuntimeException('Exception before send');
            }
        };

        return new FlowScenario('presend interceptor failing before the asynchronous send leaves no span open', [$users::class, $notifier::class, $blocking::class], [$users, $notifier, $blocking], [
            SimpleMessageChannelBuilder::createQueueChannel('users'),
            SimpleMessageChannelBuilder::createQueueChannel('notifications'),
        ], [
            self::registrationSentToTheQueue(),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('users', ExecutionPollingMetadata::createWithTestingSetup()))
                ->failingWith('Exception before send')
                ->expect(
                    ExpectedSpan::named('receive users')->withStatus(SpanStatus::Error)->containing(
                        ExpectedSpan::named('process class@anonymous::register')->withStatus(SpanStatus::Error)->containing(
                            ExpectedSpan::named('send event')->withStatus(SpanStatus::Error)->containing(
                                ExpectedSpan::named('route user.notifying')->withStatus(SpanStatus::Error),
                            ),
                        ),
                    ),
                )
                ->expectNoSpanNamed('send notifications'),
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('user.register', '2', metadata: [...$metadata, 'attempt' => 'second']))
                ->leavingWorkPending()->leavingWorkPending()
                ->expect(
                    ExpectedSpan::named('send command')->asRoot()->withAttributes(['attempt' => 'second'])->containing(
                        ExpectedSpan::named('send users'),
                    ),
                ),
        ]);
    }

    private static function failingHandlerWhoseErrorChannelSendFails(): FlowScenario
    {
        $users = new class () {
            #[Asynchronous('users')]
            #[CommandHandler('user.register', endpointId: 'user.registration')]
            public function register(string $userId): void
            {
                throw new RuntimeException('Handler failure');
            }
        };

        return new FlowScenario('failing handler whose error channel send fails', [$users::class], [$users], [
            SimpleMessageChannelBuilder::createQueueChannel('users'),
            ExceptionalQueueChannel::createWithExceptionOnSend('error_channel'),
            ServiceConfiguration::createWithDefaults()->withDefaultErrorChannel('error_channel'),
        ], [
            self::registrationSentToTheQueue(),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('users', ExecutionPollingMetadata::createWithFinishWhenNoMessages(stopOnError: false)))
                ->failingWith('Exception on send')
                ->expect(
                    ExpectedSpan::named('receive users')->ofKind(SpanKind::Consumer)->withStatus(SpanStatus::Error)->containing(
                        ExpectedSpan::named('process class@anonymous::register')->withStatus(SpanStatus::Error),
                        ExpectedSpan::named('send error_channel')->ofKind(SpanKind::Producer)->withStatus(SpanStatus::Error),
                    ),
                ),
        ]);
    }

    private static function distributedSendFailing(): FlowScenario
    {
        return new FlowScenario('distributed send failing', [], [], [
            ExceptionalQueueChannel::createWithExceptionOnSend('distributed_channel'),
            DistributedServiceMap::initialize()->withCommandMapping(targetServiceName: 'ticket_service', channelName: 'distributed_channel'),
            ServiceConfiguration::createWithDefaults()->withServiceName('user_service'),
        ], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->getDistributedBus()->sendCommand('ticket_service', 'ticket.create', 'User changed billing address', metadata: $metadata))
                ->failingWith('Exception on send')
                ->expect(
                    ExpectedSpan::named('send distributed')->ofKind(SpanKind::Producer)->withStatus(SpanStatus::Error)->containing(
                        ExpectedSpan::named('send distributed_channel')->ofKind(SpanKind::Producer)->withStatus(SpanStatus::Error),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }
}
