<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Integration\Scheduling;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Scheduling\Clock;
use Ecotone\Messaging\Scheduling\Duration;
use Ecotone\Messaging\Scheduling\EcotoneClockInterface;
use Ecotone\Test\StaticPsrClock;
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
            enableAsynchronousProcessing: [
                // 1. Turn on Delayable In Memory Pollable Channel
                SimpleMessageChannelBuilder::createQueueChannel('notifications', true),
            ]
        );

        $ecotoneTestSupport->sendCommandWithRoutingKey('order.register', new PlaceOrder('123'));

        $clock->sleep(Duration::minutes(1));

        // 2. Releasing messages awaiting for 60 seconds
        $ecotoneTestSupport->run('notifications', releaseAwaitingFor: Clock::get()->now());

        $this->assertEquals(
            1,
            count($notifier->getNotificationsOf('placedOrder'))
        );
    }

    public function test_delayed_message_observes_clock_changes_natively_by_moving_time()
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [EcotoneClockInterface::class, OrderService::class, NotificationService::class, CustomNotifier::class],
            [new OrderService(), new NotificationService(), $notifier = new CustomNotifier()],
            enableAsynchronousProcessing: [
                // 1. Turn on Delayable In Memory Pollable Channel
                SimpleMessageChannelBuilder::createQueueChannel('notifications', true),
            ]
        );

        $ecotoneTestSupport->sendCommandWithRoutingKey('order.register', new PlaceOrder('123'));

        $clock = Clock::get();
        $clock->sleep(Duration::minutes(1)->add(Duration::seconds(1)));

        // 2. Releasing messages awaiting for 60 seconds
        $ecotoneTestSupport->run('notifications');

        $this->assertEquals(
            1,
            count($notifier->getNotificationsOf('placedOrder'))
        );
    }

    public function test_clock_moves_in_time_when_not_injected(): void
    {
        EcotoneLite::bootstrapFlowTesting(
            [OrderService::class, NotificationService::class, CustomNotifier::class],
            [new OrderService(), new NotificationService(), $notifier = new CustomNotifier()],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('notifications', true),
            ]
        );

        $time = Clock::get()->now();
        $nextMoment = Clock::get()->now();

        $this->assertGreaterThan($time->getMicrosecond(), $nextMoment->getMicrosecond());
    }
}
