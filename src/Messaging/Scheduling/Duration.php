<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Messaging\Scheduling;

final class Duration
{
    public function __construct(
        private readonly int $microseconds,
    ) {
    }

    public static function microseconds(int $microseconds): self
    {
        return new self($microseconds);
    }

    public static function milliseconds(int|float $milliseconds): self
    {
        return new self((int) round($milliseconds * 1000));
    }

    public static function seconds(int|float $seconds): self
    {
        return new self((int) round($seconds * 1_000_000));
    }

    public static function minutes(int|float $seconds): self
    {
        return new self((int) round($seconds * 60 * 1_000_000));
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function absolute(): self
    {
        return new self(abs($this->microseconds));
    }

    public function inMicroseconds(): int
    {
        return $this->microseconds;
    }

    public function inMilliseconds(): int
    {
        return (int) round($this->microseconds / 1_000);
    }

    public function inSeconds(): int
    {
        return (int) round($this->microseconds / 1_000_000);
    }

    /**
     * Converts the duration to a float representing seconds.
     */
    public function toFloat(): float
    {
        return $this->microseconds / 1_000_000.0;
    }

    public function add(Duration $duration): self
    {
        return new self($this->microseconds + $duration->microseconds);
    }

    public function sub(Duration $duration): self
    {
        return new self($this->microseconds - $duration->microseconds);
    }

    public function diff(self $other): self
    {
        return new self($this->microseconds - $other->microseconds);
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->microseconds > $other->microseconds;
    }

    public function isGreaterThanOrEqual(self $other): bool
    {
        return $this->microseconds >= $other->microseconds;
    }

    public function isLessThan(self $other): bool
    {
        return $this->microseconds < $other->microseconds;
    }

    public function isLessThanOrEqual(self $other): bool
    {
        return $this->microseconds <= $other->microseconds;
    }

    public function isPositive(): bool
    {
        return $this->microseconds > 0;
    }

    public function isPositiveOrZero(): bool
    {
        return $this->microseconds >= 0;
    }

    public function isNegative(): bool
    {
        return $this->microseconds < 0;
    }

    public function isNegativeOrZero(): bool
    {
        return $this->microseconds <= 0;
    }

    public function isZero(): bool
    {
        return $this->microseconds === 0;
    }

    public function zeroIfNegative(): self
    {
        return $this->isNegative() ? self::zero() : $this;
    }

    public function getSecondsPart(): int
    {
        return (int) floor($this->microseconds / 1_000_000);
    }

    public function getMicrosecondsPart(): int
    {
        return $this->microseconds % 1_000_000;
    }

    public function __toString(): string
    {
        $seconds = $this->getSecondsPart();
        $microseconds = $this->getMicrosecondsPart();

        return sprintf('%d.%06d', $seconds, $microseconds);
    }
}
