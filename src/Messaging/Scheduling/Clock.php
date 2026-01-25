<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Messaging\Scheduling;

use Ecotone\Test\StaticPsrClock;
use Psr\Clock\ClockInterface as PsrClockInterface;

class Clock implements EcotoneClockInterface
{
    private static ?EcotoneClockInterface $globalClock = null;

    public function __construct(private PsrClockInterface $clock)
    {
        self::$globalClock = $this;
    }

    public static function createBasedOnConfig(?PsrClockInterface $clock, bool $isTestingEnabled): EcotoneClockInterface
    {
        if ($clock === null) {
            return new self($isTestingEnabled ? new StaticPsrClock('now') : self::defaultClock());
        }

        return new self($clock);
    }

    /**
     * @deprecated inject Clock interface instead
     */
    public static function get(): EcotoneClockInterface
    {
        return self::$globalClock ?? new self(self::defaultClock());
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
