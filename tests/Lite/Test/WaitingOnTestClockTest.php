<?php

declare(strict_types=1);

namespace Test\Ecotone\Lite\Test;

use DateTimeImmutable;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\ExtensionObject\TestConfiguration;
use Ecotone\Api\Gateway\EcotoneClockInterface;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Scheduling\Duration;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class WaitingOnTestClockTest extends TestCase
{
    public function test_a_wait_on_the_pinned_clock_runs_the_step_at_the_time_the_wait_moved_to(): void
    {
        $ecotone = $this->bootstrap(TestConfiguration::createWithDefaults()->withTimeAt(new DateTimeImmutable('2026-03-01 12:00:00')));
        $seenAt = [];
        $ecotone->whenWaiting(function (DateTimeImmutable $now) use (&$seenAt): void {
            $seenAt[] = $now->format('H:i:s');
        });

        $ecotone->sendCommandWithRouting('order.await', 'order-1');

        $this->assertSame(['12:00:01', '12:00:03'], $seenAt);
        $this->assertSame('2026-03-01 12:00:03', $ecotone->now()->format('Y-m-d H:i:s'));
    }

    public function test_a_wait_inside_the_step_moves_the_time_without_running_the_step_again(): void
    {
        $ecotone = $this->bootstrap(TestConfiguration::createWithDefaults()->withTimeAt(new DateTimeImmutable('2026-03-01 12:00:00')));
        $seenAt = [];
        $ecotone->whenWaiting(function (DateTimeImmutable $now) use (&$seenAt, &$ecotone): void {
            $seenAt[] = $now->format('H:i:s');
            if (count($seenAt) === 1) {
                $ecotone->sendCommandWithRouting('order.recheck', 'order-1');
            }
        });

        $ecotone->sendCommandWithRouting('order.await', 'order-1');

        $this->assertSame(['12:00:01', '12:00:08'], $seenAt);
        $this->assertSame('2026-03-01 12:00:08', $ecotone->now()->format('Y-m-d H:i:s'));
    }

    public function test_a_step_on_a_clock_that_reads_the_system_time_is_refused(): void
    {
        $ecotone = $this->bootstrap(TestConfiguration::createWithDefaults());

        $this->expectExceptionMessage('whenWaiting() runs its step each time a wait moves the pinned time forward, but the test clock reads the system time and waits for real, so the step would never run. Pin the clock before registering the step: bootstrap with TestConfiguration::createWithDefaults()->withTimeAt(new DateTimeImmutable(\'2026-03-01 12:00:00\')), or call advanceTimeBy() or changeTimeTo() on the flow test support. A StaticPsrClock taken from the container is pinned with setCurrentTime().');

        $ecotone->whenWaiting(static function (DateTimeImmutable $now): void {
        });
    }

    public function test_a_second_step_on_the_same_clock_is_refused(): void
    {
        $ecotone = $this->bootstrap(TestConfiguration::createWithDefaults()->withTimeAt(new DateTimeImmutable('2026-03-01 12:00:00')));
        $ecotone->whenWaiting(static function (DateTimeImmutable $now): void {
        });

        $this->expectExceptionMessage('whenWaiting() already has a step on this clock, and a second one would replace it, so the responders of the first step would never run. Register one step that runs every responder, as whenWaiting(function (DateTimeImmutable $now) use ($orders, $billing): void { $orders->run(\'orders\'); $billing->run(\'billing\'); }), on FlowTestSupport::whenWaiting() and StaticPsrClock::whenWaiting() alike.');

        $ecotone->whenWaiting(static function (DateTimeImmutable $now): void {
        });
    }

    private function bootstrap(TestConfiguration $testConfiguration): FlowTestSupport
    {
        $handler = new class () {
            #[CommandHandler('order.await')]
            public function await(string $orderId, EcotoneClockInterface $clock): void
            {
                $clock->sleep(Duration::seconds(1));
                $clock->sleep(Duration::seconds(2));
            }

            #[CommandHandler('order.recheck')]
            public function recheck(string $orderId, EcotoneClockInterface $clock): void
            {
                $clock->sleep(Duration::seconds(5));
            }
        };

        return EcotoneLite::bootstrapFlowTesting(
            [$handler::class],
            [$handler],
            enabledModules: [],
            testConfiguration: $testConfiguration,
        );
    }
}
