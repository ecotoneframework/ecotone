<?php

declare(strict_types=1);

namespace Ecotone\Test;

use DateTimeImmutable;
use Ecotone\Messaging\Scheduling\Duration;
use Ecotone\Messaging\Scheduling\SleepInterface;
use Psr\Clock\ClockInterface;

/**
 * licence Apache-2.0
 */
final class StaticPsrClock implements ClockInterface, SleepInterface
{
    private Duration $sleepDuration;

    public function __construct(private ?string $now = null)
    {
        $this->sleepDuration = Duration::zero();
    }

    public function now(): DateTimeImmutable
    {
        $now = $this->now === null ? new DateTimeImmutable() : new DateTimeImmutable($this->now);

        return $now->modify("+{$this->sleepDuration->zeroIfNegative()->inMicroseconds()} microseconds");
    }

    public function sleep(Duration $duration): void
    {
        $this->sleepDuration = $this->sleepDuration->add($duration);
    }
}
