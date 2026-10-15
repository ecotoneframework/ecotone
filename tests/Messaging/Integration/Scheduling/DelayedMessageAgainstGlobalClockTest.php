<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Integration\Scheduling;

use DateTimeImmutable;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Gateway\EcotoneClockInterface;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Scheduling\Duration;
use Ecotone\Test\StaticPsrClock;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Test\Ecotone\Messaging\Fixture\Scheduling\CustomNotifier;
use Test\Ecotone\Messaging\Fixture\Scheduling\NotificationService;
use Test\Ecotone\Messaging\Fixture\Scheduling\OrderService;
use Test\Ecotone\Messaging\Fixture\Scheduling\PlaceOrder;

/**
 * Class StaticGlobalClockTest
 * @package Test\Ecotone\Messaging\Unit\Scheduling
 * @author JB Cagumbay <cagumbay.jb@gmail.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class DelayedMessageAgainstGlobalClockTest extends TestCase
{
    protected function setUp(): void
    {
        parent::tearDown();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_delayed_message_observes_clock_changes()
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [EcotoneClockInterface::class, OrderService::class, NotificationService::class, CustomNotifier::class],
            [ClockInterface::class => $clock = new StaticPsrClock('2025-08-11 16:00:00'), new OrderService(), new NotificationService(), $notifier = new CustomNotifier()],
            configuration: ServiceConfiguration::createWithDefaults()->addExtensionObject(// 1. Turn on Delayable In Memory Pollable Channel
                SimpleMessageChannelBuilder::createQueueChannel('notifications', true)
            )
                ->withEnabledModulePackageNames([])
        );

        $ecotoneTestSupport->sendCommandWithRouting('order.register', new PlaceOrder('123'));

        $clock->sleep(Duration::minutes(1));

        // 2. Releasing messages awaiting for 60 seconds
        $ecotoneTestSupport->run('notifications');

        $this->assertEquals(
            1,
            count($notifier->getNotificationsOf('placedOrder'))
        );
    }

    public function test_delayed_message_is_released_when_moving_time_forward_using_change_time(): void
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [OrderService::class, NotificationService::class, CustomNotifier::class],
            [new OrderService(), new NotificationService(), $notifier = new CustomNotifier()],
            configuration: ServiceConfiguration::createWithDefaults()->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel('notifications', true))
                ->withEnabledModulePackageNames([])
        );

        $ecotoneTestSupport->changeTimeTo(new DateTimeImmutable('2025-08-11 16:00:00'));
        $ecotoneTestSupport->sendCommandWithRouting('order.register', new PlaceOrder('123'));

        $ecotoneTestSupport->run('notifications');
        $this->assertCount(0, $notifier->getNotificationsOf('placedOrder'));

        $ecotoneTestSupport->changeTimeTo(new DateTimeImmutable('2025-08-11 16:01:01'));
        $ecotoneTestSupport->run('notifications');

        $this->assertCount(1, $notifier->getNotificationsOf('placedOrder'));
    }

    public function test_delayed_message_is_released_when_advancing_time_using_duration(): void
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [OrderService::class, NotificationService::class, CustomNotifier::class],
            [new OrderService(), new NotificationService(), $notifier = new CustomNotifier()],
            configuration: ServiceConfiguration::createWithDefaults()->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel('notifications', true))
                ->withEnabledModulePackageNames([])
        );

        $ecotoneTestSupport->sendCommandWithRouting('order.register', new PlaceOrder('123'));

        $ecotoneTestSupport->run('notifications');
        $this->assertCount(0, $notifier->getNotificationsOf('placedOrder'));

        $ecotoneTestSupport->advanceTimeBy(Duration::minutes(2));
        $ecotoneTestSupport->run('notifications');

        $this->assertCount(1, $notifier->getNotificationsOf('placedOrder'));
    }

    public function test_first_change_time_call_allows_any_time(): void
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [OrderService::class, NotificationService::class, CustomNotifier::class],
            [new OrderService(), new NotificationService(), new CustomNotifier()],
            configuration: ServiceConfiguration::createWithDefaults()->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel('notifications', true))
                ->withEnabledModulePackageNames([])
        );

        $ecotoneTestSupport->changeTimeTo(new DateTimeImmutable('2020-01-01 12:00:00'));

        $this->assertEquals('2020-01-01 12:00:00', $ecotoneTestSupport->getServiceFromContainer(EcotoneClockInterface::class)->now()->format('Y-m-d H:i:s'));
    }

    public function test_change_time_throws_exception_when_moving_backwards_after_first_setup(): void
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [OrderService::class, NotificationService::class, CustomNotifier::class],
            [new OrderService(), new NotificationService(), new CustomNotifier()],
            configuration: ServiceConfiguration::createWithDefaults()->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel('notifications', true))
                ->withEnabledModulePackageNames([])
        );

        $ecotoneTestSupport->changeTimeTo(new DateTimeImmutable('2025-08-11 17:00:00'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot move time backwards');

        $ecotoneTestSupport->changeTimeTo(new DateTimeImmutable('2025-08-11 16:30:00'));
    }

    public function test_time_advances_before_change_time_is_called(): void
    {
        $clock = new StaticPsrClock();

        $time1 = $clock->now();
        usleep(1000);
        $time2 = $clock->now();

        $this->assertGreaterThan($time1, $time2);
    }

    public function test_time_advances_when_constructed_with_now_string(): void
    {
        $clock = new StaticPsrClock('now');

        $time1 = $clock->now();
        usleep(1000);
        $time2 = $clock->now();

        $this->assertGreaterThan($time1, $time2);
    }

    public function test_time_freezes_after_advance_time_with_duration(): void
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [OrderService::class, NotificationService::class, CustomNotifier::class],
            [ClockInterface::class => new StaticPsrClock(), new OrderService(), new NotificationService(), new CustomNotifier()],
            configuration: ServiceConfiguration::createWithDefaults()->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel('notifications', true))
                ->withEnabledModulePackageNames([])
        );

        $ecotoneTestSupport->advanceTimeBy(Duration::seconds(1));

        $clock = $ecotoneTestSupport->getServiceFromContainer(ClockInterface::class);
        $time1 = $clock->now();
        usleep(1000);
        $time2 = $clock->now();

        $this->assertEquals($time1, $time2);
    }

    public function test_time_advances_when_constructed_with_null(): void
    {
        $clock = new StaticPsrClock(null);

        $time1 = $clock->now();
        usleep(1000);
        $time2 = $clock->now();

        $this->assertGreaterThan($time1, $time2);
    }

    public function test_time_freezes_after_sleep_is_called_with_fixed_time(): void
    {
        $clock = new StaticPsrClock('2025-08-11 16:00:00');

        $clock->sleep(Duration::seconds(1));

        $time1 = $clock->now();
        usleep(1000);
        $time2 = $clock->now();

        $this->assertEquals($time1, $time2);
    }

    public function test_time_continues_advancing_after_sleep_when_using_now(): void
    {
        $clock = new StaticPsrClock('now');

        $clock->sleep(Duration::seconds(1));

        $time1 = $clock->now();
        usleep(1000);
        $time2 = $clock->now();

        $this->assertGreaterThan($time1, $time2);
    }
}
