<?php

declare(strict_types=1);

namespace Ecotone\Test;

use Closure;
use DateTimeImmutable;
use Ecotone\Api\Scheduling\Duration;
use Ecotone\Api\Scheduling\SleepInterface;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Psr\Clock\ClockInterface;

/**
 * licence Apache-2.0
 */
final class StaticPsrClock implements ClockInterface, SleepInterface
{
    private bool $hasBeenChanged = false;
    private ?DateTimeImmutable $now = null;
    private ?DateTimeImmutable $pinnedTime = null;
    private ?Closure $whenWaiting = null;
    private bool $isRunningWaitStep = false;

    public function __construct(?string $now = null)
    {
        $this->now = ($now === null || $now === 'now') ? null : new DateTimeImmutable($now);
        $this->pinnedTime = $this->now;
        $this->hasBeenChanged = $this->now !== null;
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
        $this->runWaitStep();
    }

    /**
     * @param Closure(DateTimeImmutable): void $step
     */
    public function whenWaiting(Closure $step): void
    {
        if ($this->now === null) {
            throw InvalidArgumentException::create("whenWaiting() runs its step each time a wait moves the pinned time forward, but the test clock reads the system time and waits for real, so the step would never run. Pin the clock before registering the step: bootstrap with TestConfiguration::createWithDefaults()->withTimeAt(new DateTimeImmutable('2026-03-01 12:00:00')), or call advanceTimeBy() or changeTimeTo() on the flow test support. A StaticPsrClock taken from the container is pinned with setCurrentTime().");
        }

        if ($this->whenWaiting !== null) {
            throw InvalidArgumentException::create("whenWaiting() already has a step on this clock, and a second one would replace it, so the responders of the first step would never run. Register one step that runs every responder, as whenWaiting(function (DateTimeImmutable \$now) use (\$orders, \$billing): void { \$orders->run('orders'); \$billing->run('billing'); }), on FlowTestSupport::whenWaiting() and StaticPsrClock::whenWaiting() alike.");
        }

        $this->whenWaiting = $step;
    }

    private function runWaitStep(): void
    {
        if ($this->whenWaiting === null || $this->isRunningWaitStep) {
            return;
        }

        $this->isRunningWaitStep = true;
        try {
            ($this->whenWaiting)($this->now);
        } finally {
            $this->isRunningWaitStep = false;
        }
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

    public function executeAt(DateTimeImmutable $time, callable $execution): mixed
    {
        $now = $this->now;
        $this->now = $time;
        try {
            return $execution();
        } finally {
            $this->now = $now;
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
