<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Messaging\Scheduling;

use Ecotone\Api\Gateway\EcotoneClockInterface;
use Ecotone\Api\Scheduling\DatePoint;
use Ecotone\Api\Scheduling\Duration;
use Ecotone\Api\Scheduling\SleepInterface;
use Psr\Clock\ClockInterface as PsrClockInterface;

class Clock implements EcotoneClockInterface
{
    public function __construct(private PsrClockInterface $clock)
    {
    }

    public static function createWithFallback(?PsrClockInterface $clock, PsrClockInterface $fallbackClock): EcotoneClockInterface
    {
        return new self($clock ?? $fallbackClock);
    }

    public function internalClock(): PsrClockInterface
    {
        return $this->clock;
    }

    public function now(): DatePoint
    {
        $now = $this->clock->now();
        if (! $now instanceof DatePoint) {
            $now = DatePoint::createFromInterface($now);
        }

        return $now;
    }

    public function sleep(Duration $duration): void
    {
        if ($this->clock instanceof SleepInterface) {
            $this->clock->sleep($duration);
            return;
        }

        if ($duration->isNegativeOrZero()) {
            return;
        }

        self::defaultClock()->sleep($duration);
    }

    private static function defaultClock(): EcotoneClockInterface
    {
        return new NativeClock();
    }
}
