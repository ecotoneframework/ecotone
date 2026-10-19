<?php

declare(strict_types=1);

namespace Ecotone\Test\Flow;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Tracing\SpanAttribute;
use Ecotone\Api\Tracing\SpanKind;

/**
 * licence Apache-2.0
 */
final class EventFlowScenarios
{
    /**
     * @return list<FlowScenario>
     */
    public static function all(): array
    {
        return [
            self::eventReachingTwoSubscribersThatEachSendACommand(),
            self::commandPublishingAnEventWhoseSubscriberSendsACommand(),
        ];
    }

    private static function eventReachingTwoSubscribersThatEachSendACommand(): FlowScenario
    {
        $users = self::users();
        $first = new class () {
            #[EventHandler('merchant.created', endpointId: 'merchant.to.user')]
            public function merchantToUser(string $merchantId, CommandBus $commandBus): void
            {
                $commandBus->sendWithRouting('user.register', $merchantId);
            }
        };
        $second = new class () {
            #[EventHandler('merchant.created', endpointId: 'merchant.to.second.user')]
            public function merchantToUser(string $merchantId, CommandBus $commandBus): void
            {
                $commandBus->sendWithRouting('user.register', $merchantId . '-second');
            }
        };
        $registration = fn (string $endpointId) => ExpectedSpan::named('route ' . $endpointId)->ofKind(SpanKind::Producer)->withAttributes([SpanAttribute::ENDPOINT_ID => $endpointId, SpanAttribute::MESSAGING_DESTINATION_NAME => $endpointId . '.target'])->containing(
            ExpectedSpan::named('process class@anonymous::merchantToUser')->withAttributes([SpanAttribute::ENDPOINT_ID => $endpointId])->containing(
                ExpectedSpan::named('send command')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'user.register'])->containing(
                    ExpectedSpan::named('process class@anonymous::register')->withAttributes([SpanAttribute::ENDPOINT_ID => 'user.registration']),
                ),
            ),
        );

        return new FlowScenario('event reaching two subscribers that each send a command', [$users::class, $first::class, $second::class], [$users, $first, $second], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->publishEventWithRouting('merchant.created', '1', metadata: $metadata))
                ->expect(
                    ExpectedSpan::named('send event')->ofKind(SpanKind::Server)->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'merchant.created'])->containing(
                        $registration('merchant.to.user'),
                        $registration('merchant.to.second.user'),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function commandPublishingAnEventWhoseSubscriberSendsACommand(): FlowScenario
    {
        $merchants = new class () {
            #[CommandHandler('merchant.create', endpointId: 'merchant.creation')]
            public function create(string $merchantId, EventBus $eventBus): void
            {
                $eventBus->publishWithRouting('merchant.created', $merchantId);
            }
        };
        $users = self::users();
        $subscriber = new class () {
            #[EventHandler('merchant.created', endpointId: 'merchant.to.user')]
            public function merchantToUser(string $merchantId, CommandBus $commandBus): void
            {
                $commandBus->sendWithRouting('user.register', $merchantId);
            }
        };

        return new FlowScenario('command publishing an event whose subscriber sends a command', [$merchants::class, $users::class, $subscriber::class], [$merchants, $users, $subscriber], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('merchant.create', '1', metadata: $metadata))
                ->expect(
                    ExpectedSpan::named('send command')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'merchant.create'])->containing(
                        ExpectedSpan::named('process class@anonymous::create')->withAttributes([SpanAttribute::ENDPOINT_ID => 'merchant.creation'])->containing(
                            ExpectedSpan::named('send event')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'merchant.created'])->containing(
                                ExpectedSpan::named('route merchant.to.user')->ofKind(SpanKind::Producer)->containing(
                                    ExpectedSpan::named('process class@anonymous::merchantToUser')->containing(
                                        ExpectedSpan::named('send command')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'user.register'])->containing(
                                            ExpectedSpan::named('process class@anonymous::register'),
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

    private static function users(): object
    {
        return new class () {
            #[CommandHandler('user.register', endpointId: 'user.registration')]
            public function register(string $userId): void
            {
            }
        };
    }
}
