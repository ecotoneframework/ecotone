<?php

declare(strict_types=1);

namespace Test\Ecotone\Tracing;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\Presend;
use Ecotone\Api\ExtensionObject\DistributedServiceMap;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Tracing\InMemorySpanRecorder;
use Ecotone\Api\Tracing\Span;
use Ecotone\Api\Tracing\SpanKind;
use Ecotone\Api\Tracing\SpanStatus;
use Ecotone\Test\LicenceTesting;
use RuntimeException;
use Throwable;

/**
 * licence Apache-2.0
 * @internal
 */
final class TracingEdgeCasesTest extends TracingTestCase
{
    public function test_an_interceptor_failing_before_the_asynchronous_send_leaves_no_open_span_behind(): void
    {
        $blockingInterceptor = new class () {
            #[Presend(pointcut: EventHandler::class)]
            public function block(): void
            {
                throw new RuntimeException('Exception before send');
            }
        };
        $recorder = new InMemorySpanRecorder();
        $ecotone = $this->bootstrapWithEventForwardedToSecondChannel($recorder, [
            SimpleMessageChannelBuilder::createQueueChannel('async_channel'),
            SimpleMessageChannelBuilder::createQueueChannel('failing_channel'),
        ], [$blockingInterceptor]);
        $ecotone->sendCommandWithRouting('user.register', '1');

        $caught = $this->runConsumer($ecotone);
        $this->assertStringContainsString('Exception before send', $caught?->getMessage() ?? '');

        $recorder->clear();
        $ecotone->sendCommandWithRouting('user.register', '2');
        $this->assertSame('send command', self::rootSpan($recorder)->name);
    }

    public function test_a_command_carrying_a_trace_carrier_continues_that_trace_as_a_child(): void
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
            ->sendCommandWithRouting('user.register', '1', metadata: [MessageHeaders::TRACEPARENT => '00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-01']);

        $commandBus = self::singleSpanNamed('send command', $recorder);
        $this->assertSame('0af7651916cd43dd8448eb211c80319c', $commandBus->traceId);
        $this->assertSame('b7ad6b7169203331', $commandBus->parentSpanId);
        $this->assertSame($commandBus->spanId, self::singleSpanNamed('process class@anonymous::register', $recorder)->parentSpanId);
    }

    public function test_the_trace_carrier_of_the_incoming_command_does_not_leak_into_the_handler_span_parent(): void
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
            ->sendCommandWithRouting('user.register', '1', metadata: [MessageHeaders::TRACEPARENT => '00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-01']);

        $handler = self::singleSpanNamed('process class@anonymous::register', $recorder);
        $this->assertNotSame('b7ad6b7169203331', $handler->parentSpanId);
    }

    public function test_distributed_bus_send_is_a_producer_span_carrying_the_trace_to_the_other_service(): void
    {
        $recorder = new InMemorySpanRecorder();
        $ticketService = new class () {
            public ?string $traceparent = null;

            #[CommandHandler('ticket.create')]
            public function create(string $description, #[Header(MessageHeaders::TRACEPARENT)] ?string $traceparent = null): void
            {
                $this->traceparent = $traceparent;
            }
        };

        EcotoneLite::bootstrapFlowTesting(
            [$ticketService::class],
            [$ticketService, ...self::recordingSpansInto($recorder)],
            [
                self::tracingConfiguration(),
                SimpleMessageChannelBuilder::createQueueChannel('distributed_channel'),
                DistributedServiceMap::initialize()->withCommandMapping(targetServiceName: 'ticket_service', channelName: 'distributed_channel'),
                ServiceConfiguration::createWithDefaults()->withServiceName('ticket_service'),
            ],
            licenceKey: LicenceTesting::VALID_LICENCE,
        )
            ->getDistributedBus()->sendCommand('ticket_service', 'ticket.create', 'User changed billing address');

        $distributedBus = self::singleSpanNamed('send distributed', $recorder);
        $this->assertSame(SpanKind::Producer, $distributedBus->kind);
        $this->assertSame($distributedBus->spanId, self::singleSpanNamed('send distributed_channel', $recorder)->parentSpanId);
    }

    private function bootstrapWithEventForwardedToSecondChannel(InMemorySpanRecorder $recorder, array $channelsToRegister, array $servicesToRegister = []): FlowTestSupport
    {
        $users = new class () {
            #[Asynchronous('async_channel')]
            #[CommandHandler('user.register', endpointId: 'userRegisterEndpoint')]
            public function register(string $userId, EventBus $eventBus): void
            {
                $eventBus->publishWithRouting('user.registered', $userId);
            }
        };
        $notifier = new class () {
            #[Asynchronous('failing_channel')]
            #[EventHandler('user.registered', endpointId: 'userNotifierEndpoint')]
            public function notify(string $userId): void
            {
            }
        };

        return EcotoneLite::bootstrapFlowTesting(
            [$users::class, $notifier::class, ...array_map(fn (object $service): string => $service::class, $servicesToRegister)],
            [$users, $notifier, ...self::recordingSpansInto($recorder), ...$servicesToRegister],
            [self::tracingConfiguration(), ...$channelsToRegister],
            enabledModules: [ModulePackageList::CORE_PACKAGE],
        );
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

    private function runConsumer(FlowTestSupport $ecotone, string $channel = 'async_channel'): ?Throwable
    {
        try {
            $ecotone->run($channel, ExecutionPollingMetadata::createWithTestingSetup());
        } catch (Throwable $failure) {
            return $failure;
        }

        return null;
    }

    private static function singleSpanNamed(string $name, InMemorySpanRecorder $recorder): Span
    {
        $spans = array_values(array_filter($recorder->recordedSpans(), fn (Span $span): bool => $span->name === $name));
        self::assertCount(1, $spans, 'Expected exactly one span named ' . $name . ', got: ' . implode(', ', self::spanNamesWithoutFrameworkNoise($recorder->recordedSpans())));

        return $spans[0];
    }

    private static function assertEverySpanNamedHasStatus(string $name, SpanStatus $status, InMemorySpanRecorder $recorder): void
    {
        $spans = array_values(array_filter($recorder->recordedSpans(), fn (Span $span): bool => $span->name === $name));
        self::assertNotEmpty($spans, 'Expected spans named ' . $name . ', got: ' . implode(', ', self::spanNamesWithoutFrameworkNoise($recorder->recordedSpans())));
        foreach ($spans as $span) {
            self::assertSame($status, $span->status, $name);
        }
    }

    private static function rootSpan(InMemorySpanRecorder $recorder): Span
    {
        $roots = array_values(array_filter($recorder->recordedSpans(), fn (Span $span): bool => $span->isRoot()));
        self::assertCount(1, $roots, 'Expected exactly one root span, got: ' . implode(', ', array_map(fn (Span $span): string => $span->name, $roots)));

        return $roots[0];
    }
}
