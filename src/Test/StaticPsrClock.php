<?php

declare(strict_types=1);

namespace Ecotone\Test;

use DateTimeImmutable;
use Ecotone\Api\Scheduling\Duration;
use Ecotone\Api\Scheduling\SleepInterface;
use Psr\Clock\ClockInterface;

/**
 * licence Apache-2.0
 */
final class StaticPsrClock implements ClockInterface, SleepInterface
{
    private bool $hasBeenChanged = false;
    private ?DateTimeImmutable $now = null;
    private ?DateTimeImmutable $pinnedTime = null;

    public function __construct(?string $now = null)
    {
        $this->now = ($now === null || $now === 'now') ? null : new DateTimeImmutable($now);
        $this->pinnedTime = $this->now;
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
        $this->pinnedTime = $time;
        $this->hasBeenChanged = true;
    }

    public function pinCurrentTime(): void
    {
        $this->pinnedTime = $this->now;
    }

    public function returnToPinnedTime(): void
    {
        if ($this->pinnedTime !== null) {
            $this->now = $this->pinnedTime;
        }
    }

    public function executeAtPinnedTime(callable $execution): mixed
    {
        if ($this->pinnedTime === null) {
            return $execution();
        }

        $consumerTime = $this->now();
        $this->now = $this->pinnedTime;
        try {
            return $execution();
        } finally {
            $this->now = $consumerTime;
        }
    }
}
