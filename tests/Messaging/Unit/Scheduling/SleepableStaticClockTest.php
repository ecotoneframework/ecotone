<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Scheduling;

use DateTimeImmutable;
use Ecotone\Messaging\Scheduling\Clock;
use Ecotone\Messaging\Scheduling\Duration;
use Ecotone\Messaging\Scheduling\SleepInterface;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

/**
 * Class SleepableStaticClockTest
 * @package Test\Ecotone\Messaging\Unit\Scheduling
 * @author JB Cagumbay <cagumbay.jb@gmail.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class SleepableStaticClockTest extends TestCase
{
    public function test_when_given_psr_clock_instance_with_sleep_support_it_increase_the_clock()
    {
        $staticClock = $this->createStaticClock('2025-08-11 16:00:00');
        $clock = new Clock($staticClock);
        $clock->sleep(Duration::seconds(30));

        $this->assertEquals('2025-08-11 16:00:30', $clock->now()->format('Y-m-d H:i:s'));
    }

    /**
     * @description Create static clock with given date time to mimic external PsrClockInterface implementation
     * as Ecotone's Clock dependency override.
     * @param string $currentDateTime
     * @return ClockInterface
     */
    private function createStaticClock(string $currentDateTime): ClockInterface
    {
        return new class ($currentDateTime) implements ClockInterface, SleepInterface {
            private static DateTimeImmutable $now;

            public function __construct(string $now)
            {
                self::$now = new DateTimeImmutable($now);
            }

            public function now(): DateTimeImmutable
            {
                return self::$now;
            }

            public function sleep(Duration $duration): void
            {
                self::$now = self::$now->modify("+{$duration->zeroIfNegative()->inMicroseconds()} microseconds");
            }
        };
    }
}
