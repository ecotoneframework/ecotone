<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Execution;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Test\CoroutineTesting;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Swoole\Coroutine;

/**
 * licence Apache-2.0
 * @internal
 */
#[RequiresMethod(Coroutine::class, 'create')]
final class CoroutineExecutionsTest extends TestCase
{
    public function test_coroutine_started_by_a_handler_propagates_the_headers_of_the_message_being_handled(): void
    {
        $ecotone = $this->bootstrapParcels();

        CoroutineTesting::runAtOnce(
            fn () => $ecotone->sendCommandWithRouting('parcel.sendFromCoroutine', ['parcelId' => 'first', 'pauseSeconds' => 0.05], metadata: ['tenant' => 'tenant-of-first']),
            fn () => $ecotone->sendCommandWithRouting('parcel.sendFromCoroutine', ['parcelId' => 'second'], metadata: ['tenant' => 'tenant-of-second']),
        );

        $this->assertEquals(
            ['first' => 'tenant-of-first', 'second' => 'tenant-of-second'],
            $ecotone->sendQueryWithRouting('parcel.tenantSeenByEventHandler')
        );
    }

    public function test_coroutine_started_by_a_coroutine_a_handler_started_propagates_the_headers_of_the_message_being_handled(): void
    {
        $ecotone = $this->bootstrapParcels();

        CoroutineTesting::runAtOnce(
            fn () => $ecotone->sendCommandWithRouting('parcel.sendFromCoroutineOfCoroutine', ['parcelId' => 'first'], metadata: ['tenant' => 'tenant-of-first']),
        );

        $this->assertSame(['first' => 'tenant-of-first'], $ecotone->sendQueryWithRouting('parcel.tenantSeenByEventHandler'));
    }

    public function test_coroutines_a_handler_runs_outside_of_any_coroutine_propagate_the_headers_of_the_message_being_handled(): void
    {
        $ecotone = $this->bootstrapParcels();

        $ecotone->sendCommandWithRouting('parcel.sendFromCoroutinesRunByHandler', ['parcelId' => 'first'], metadata: ['tenant' => 'tenant-of-first']);

        $this->assertSame(['first' => 'tenant-of-first'], $ecotone->sendQueryWithRouting('parcel.tenantSeenByEventHandler'));
    }

    public function test_coroutine_outliving_its_handler_does_not_propagate_the_headers_of_the_next_message(): void
    {
        $ecotone = $this->bootstrapParcels();

        CoroutineTesting::runAtOnce(
            function () use ($ecotone): void {
                $ecotone->sendCommandWithRouting('parcel.sendFromCoroutineLeftBehind', ['parcelId' => 'left behind', 'pauseSeconds' => 0.05], metadata: ['tenant' => 'tenant-of-left-behind']);
                $ecotone->sendCommandWithRouting('parcel.pause', ['pauseSeconds' => 0.2], metadata: ['tenant' => 'tenant-of-next-message']);
            },
        );

        $this->assertSame(['left behind' => null], $ecotone->sendQueryWithRouting('parcel.tenantSeenByEventHandler'));
    }

    public function test_first_messages_handled_at_once_after_boot_are_all_handled(): void
    {
        $counter = new class () {
            private int $counted = 0;

            #[CommandHandler('counter.count')]
            public function count(): void
            {
                $this->counted++;
            }

            #[QueryHandler('counter.counted')]
            public function counted(): int
            {
                return $this->counted;
            }
        };
        $servicesTakingTimeToBuild = new class ([$counter::class => $counter]) implements ContainerInterface {
            public function __construct(private array $services)
            {
            }

            public function get(string $id): mixed
            {
                usleep(20_000);

                return $this->services[$id];
            }

            public function has(string $id): bool
            {
                return isset($this->services[$id]);
            }
        };
        $ecotone = EcotoneLite::bootstrapFlowTesting([$counter::class], $servicesTakingTimeToBuild);

        CoroutineTesting::runAtOnce(
            fn () => $ecotone->sendCommandWithRouting('counter.count'),
            fn () => $ecotone->sendCommandWithRouting('counter.count'),
            fn () => $ecotone->sendCommandWithRouting('counter.count'),
        );

        $this->assertSame(3, $ecotone->sendQueryWithRouting('counter.counted'));
    }

    #[RequiresPhpExtension('pcntl')]
    #[RequiresPhpExtension('posix')]
    public function test_every_consumer_coroutine_stops_when_the_process_is_asked_to_terminate(): void
    {
        $orders = new class () {
            #[Asynchronous('orders')]
            #[CommandHandler('order.place', endpointId: 'order.place.endpoint')]
            public function place(): void
            {
            }
        };
        $ecotone = EcotoneLite::bootstrap(
            [$orders::class],
            [$orders],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('orders')],
            enabledModules: [],
        );
        $consumeUpToTenSeconds = ExecutionPollingMetadata::createWithDefaults()->withExecutionTimeLimitInMilliseconds(10_000)->withFinishWhenNoMessages(false);
        $stoppedAfterSeconds = [];
        $startedAt = microtime(true);
        $consume = static function () use ($ecotone, $consumeUpToTenSeconds, &$stoppedAfterSeconds, $startedAt): void {
            $ecotone->run('orders', $consumeUpToTenSeconds);
            $stoppedAfterSeconds[] = microtime(true) - $startedAt;
        };

        CoroutineTesting::runAtOnce(
            $consume,
            $consume,
            static function (): void {
                usleep(300_000);
                posix_kill(getmypid(), SIGTERM);
            },
        );

        $this->assertCount(2, $stoppedAfterSeconds);
        $this->assertLessThan(5, max($stoppedAfterSeconds));
    }

    private function bootstrapParcels(): FlowTestSupport
    {
        $parcels = new class () {
            private array $tenantSeenByEventHandler = [];

            #[CommandHandler('parcel.sendFromCoroutine')]
            public function sendFromCoroutine(array $command, EventBus $eventBus): void
            {
                $sent = new Coroutine\Channel(1);
                Coroutine::create(static function () use ($command, $eventBus, $sent): void {
                    usleep((int) (($command['pauseSeconds'] ?? 0) * 1_000_000));
                    $eventBus->publish(new ParcelWasSent($command['parcelId']));
                    $sent->push(true);
                });
                $sent->pop();
            }

            #[CommandHandler('parcel.sendFromCoroutineOfCoroutine')]
            public function sendFromCoroutineOfCoroutine(array $command, EventBus $eventBus): void
            {
                $sent = new Coroutine\Channel(1);
                Coroutine::create(static function () use ($command, $eventBus, $sent): void {
                    $sentByCoroutineOfCoroutine = new Coroutine\Channel(1);
                    Coroutine::create(static function () use ($command, $eventBus, $sentByCoroutineOfCoroutine): void {
                        usleep(10_000);
                        $eventBus->publish(new ParcelWasSent($command['parcelId']));
                        $sentByCoroutineOfCoroutine->push(true);
                    });
                    $sentByCoroutineOfCoroutine->pop();
                    $sent->push(true);
                });
                $sent->pop();
            }

            #[CommandHandler('parcel.sendFromCoroutinesRunByHandler')]
            public function sendFromCoroutinesRunByHandler(array $command, EventBus $eventBus): void
            {
                $scheduler = new Coroutine\Scheduler();
                $scheduler->add(static function () use ($command, $eventBus): void {
                    Coroutine::create(static fn () => $eventBus->publish(new ParcelWasSent($command['parcelId'])));
                });
                $scheduler->start();
            }

            #[CommandHandler('parcel.sendFromCoroutineLeftBehind')]
            public function sendFromCoroutineLeftBehind(array $command, EventBus $eventBus): void
            {
                Coroutine::create(static function () use ($command, $eventBus): void {
                    usleep((int) ($command['pauseSeconds'] * 1_000_000));
                    $eventBus->publish(new ParcelWasSent($command['parcelId']));
                });
            }

            #[CommandHandler('parcel.pause')]
            public function pause(array $command): void
            {
                usleep((int) ($command['pauseSeconds'] * 1_000_000));
            }

            #[EventHandler]
            public function when(ParcelWasSent $event, #[Header('tenant')] ?string $tenant = null): void
            {
                $this->tenantSeenByEventHandler[$event->parcelId] = $tenant;
            }

            #[QueryHandler('parcel.tenantSeenByEventHandler')]
            public function tenantSeenByEventHandler(): array
            {
                return $this->tenantSeenByEventHandler;
            }
        };

        return EcotoneLite::bootstrapFlowTesting([$parcels::class], [$parcels]);
    }
}

final class ParcelWasSent
{
    public function __construct(public string $parcelId)
    {
    }
}
