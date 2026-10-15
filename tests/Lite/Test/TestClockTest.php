<?php

declare(strict_types=1);

namespace Test\Ecotone\Lite\Test;

use DateTimeImmutable;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\Delayed;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Scheduling\Duration;
use Ecotone\Api\Scheduling\TimeSpan;
use Ecotone\Test\StaticPsrClock;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

/**
 * licence Apache-2.0
 * @internal
 */
final class TestClockTest extends TestCase
{
    public function test_changing_time_to_the_current_instant_is_a_no_op(): void
    {
        $clock = new StaticPsrClock();
        $ecotone = $this->bootstrap($this->timeRecordingHandler($clock), $clock);

        $ecotone->changeTimeTo(new DateTimeImmutable('2026-03-01 10:05:00'));
        $ecotone->changeTimeTo(new DateTimeImmutable('2026-03-01 10:05:00'));

        $this->assertEquals(new DateTimeImmutable('2026-03-01 10:05:00'), $clock->now());
    }

    public function test_changing_time_backwards_names_the_current_and_requested_time(): void
    {
        $clock = new StaticPsrClock();
        $ecotone = $this->bootstrap($this->timeRecordingHandler($clock), $clock);
        $ecotone->changeTimeTo(new DateTimeImmutable('2026-03-01 13:00:00'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot move time backwards: the test clock is at 2026-03-01 13:00:00.000000 and you requested 2026-03-01 12:00:00.000000. Request a later time, or use advanceTimeBy() to move forward relative to the current time.');

        $ecotone->changeTimeTo(new DateTimeImmutable('2026-03-01 12:00:00'));
    }

    public function test_running_a_consumer_does_not_move_the_time_set_by_the_test(): void
    {
        $clock = new StaticPsrClock();
        $ecotone = $this->bootstrap($this->timeRecordingHandler($clock), $clock);
        $ecotone->changeTimeTo(new DateTimeImmutable('2026-03-01 13:00:00'));

        $ecotone->run('async');

        $this->assertEquals(new DateTimeImmutable('2026-03-01 13:00:00'), $clock->now());
        $ecotone->changeTimeTo(new DateTimeImmutable('2026-03-01 13:00:00'));
    }

    public function test_every_message_handled_in_one_run_sees_the_time_set_by_the_test(): void
    {
        $clock = new StaticPsrClock();
        $handler = $this->timeRecordingHandler($clock);
        $ecotone = $this->bootstrap($handler, $clock);
        $ecotone->changeTimeTo(new DateTimeImmutable('2026-03-01 13:00:00'));

        $ecotone
            ->publishEventWithRouting('order.placed', 'order-1')
            ->publishEventWithRouting('order.placed', 'order-2')
            ->publishEventWithRouting('order.placed', 'order-3')
            ->run('async');

        $this->assertSame(
            ['order-1' => '13:00:00.000000', 'order-2' => '13:00:00.000000', 'order-3' => '13:00:00.000000'],
            $handler->seenAt,
        );
    }

    public function test_advancing_time_by_spans_adds_up_and_releases_a_delayed_message(): void
    {
        $clock = new StaticPsrClock();
        $handler = new class () {
            public array $expired = [];

            #[Asynchronous('async')]
            #[Delayed(new TimeSpan(hours: 24))]
            #[EventHandler('order.placed', endpointId: 'expireOrder')]
            public function expire(string $orderId): void
            {
                $this->expired[] = $orderId;
            }
        };
        $ecotone = $this->bootstrap($handler, $clock);
        $ecotone->changeTimeTo(new DateTimeImmutable('2026-03-01 12:00:00'));
        $ecotone->publishEventWithRouting('order.placed', 'order-1');

        $ecotone->advanceTimeBy(TimeSpan::withHours(23))->run('async');
        $this->assertSame([], $handler->expired);

        $ecotone->advanceTimeBy(Duration::minutes(60))->run('async');
        $this->assertSame(['order-1'], $handler->expired);
        $this->assertEquals(new DateTimeImmutable('2026-03-02 12:00:00'), $clock->now());
    }

    public function test_due_delayed_messages_are_released_in_the_order_they_became_due(): void
    {
        $handler = new class () {
            public array $handled = [];

            #[Asynchronous('async')]
            #[Delayed(new TimeSpan(hours: 24))]
            #[EventHandler('order.placed', endpointId: 'expireOrder')]
            public function expire(string $orderId): void
            {
                $this->handled[] = 'expire ' . $orderId;
            }

            #[Asynchronous('async')]
            #[Delayed(new TimeSpan(hours: 1))]
            #[EventHandler('shipping.requested', endpointId: 'reserveShippingSlot')]
            public function reserve(string $orderId): void
            {
                $this->handled[] = 'reserve ' . $orderId;
            }
        };
        $ecotone = $this->bootstrap($handler, new StaticPsrClock());
        $ecotone->changeTimeTo(new DateTimeImmutable('2026-03-01 12:00:00'));
        $ecotone->publishEventWithRouting('order.placed', 'order-1');
        $ecotone->publishEventWithRouting('shipping.requested', 'order-1');

        $ecotone->changeTimeTo(new DateTimeImmutable('2026-03-02 13:00:00'))->run('async');

        $this->assertSame(['reserve order-1', 'expire order-1'], $handler->handled);
    }

    private function timeRecordingHandler(ClockInterface $clock): object
    {
        return new class ($clock) {
            public array $seenAt = [];

            public function __construct(private ClockInterface $clock)
            {
            }

            #[Asynchronous('async')]
            #[EventHandler('order.placed', endpointId: 'recordPlacementTime')]
            public function record(string $orderId): void
            {
                $this->seenAt[$orderId] = $this->clock->now()->format('H:i:s.u');
            }
        };
    }

    private function bootstrap(object $handler, ClockInterface $clock): FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            [$handler::class],
            [$handler, ClockInterface::class => $clock],
            ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([SimpleMessageChannelBuilder::createQueueChannel('async')])
                ->withEnabledModulePackageNames([]),
        );
    }
}
