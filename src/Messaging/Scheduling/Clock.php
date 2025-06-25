<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Messaging\Scheduling;

use Psr\Clock\ClockInterface as PsrClockInterface;

class Clock implements EcotoneClockInterface
{
    private static ?EcotoneClockInterface $globalClock = null;

    public function __construct(
        private readonly ?PsrClockInterface $clock = null,
    ) {
    }

    public static function set(PsrClockInterface $clock): void
    {
        self::$globalClock = $clock instanceof EcotoneClockInterface ? $clock : new self($clock);
    }

    /**
     * @deprecated inject Clock interface instead
     */
    public static function get(): EcotoneClockInterface
    {
        return self::$globalClock ??= new NativeClock();
    }

    public function now(): DatePoint
    {
        $now = ($this->clock ?? self::get())->now();
        if (! $now instanceof DatePoint) {
            $now = DatePoint::createFromInterface($now);
        }

        return $now;
    }

    public function sleep(Duration $duration): void
    {
        $clock = $this->clock ?? self::get();

        if ($clock instanceof EcotoneClockInterface) {
            $clock->sleep($duration);
        } else {
            (new NativeClock())->sleep($duration);
        }
    }
}
