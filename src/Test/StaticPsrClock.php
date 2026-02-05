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
    private bool $hasBeenChanged = false;
    private ?DateTimeImmutable $now = null;

    public function __construct(?string $now = null)
    {
        $this->now = ($now === null || $now === 'now') ? null : new DateTimeImmutable($now);
    }

    public function now(): DateTimeImmutable
    {
        if ($this->now !== null) {
            return $this->now;
        }

        return new DateTimeImmutable('now');
    }

    public function sleep(Duration $duration): void
    {
        if ($duration->isNegativeOrZero()) {
            return;
        }

        if ($this->now === null) {

            usleep($duration->inMicroseconds());
            return;
        }

        $this->now = $this->now()->modify("+{$duration->inMicroseconds()} microseconds");
    }

    public function hasBeenChanged(): bool
    {
        return $this->hasBeenChanged;
    }

    public function setCurrentTime(DateTimeImmutable $time): void
    {
        $this->now = $time;
        $this->hasBeenChanged = true;
    }
}
