<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Scheduling;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;

/**
 * licence Apache-2.0
 */
final class TimeSpan implements DefinedObject
{
    public function __construct(
        public int $milliseconds = 0,
        public int $seconds = 0,
        public int $minutes = 0,
        public int $hours = 0,
        public int $days = 0,
    ) {

    }

    public static function withMilliseconds(int $milliseconds): self
    {
        return new self(milliseconds: $milliseconds);
    }

    public static function withSeconds(int $seconds): self
    {
        return new self(seconds: $seconds);
    }

    public static function withMinutes(int $minutes): self
    {
        return new self(minutes: $minutes);
    }

    public static function withHours(int $hours): self
    {
        return new self(hours: $hours);
    }

    public static function withDays(int $days): self
    {
        return new self(days: $days);
    }

    public function toMilliseconds(): int
    {
        return $this->milliseconds + $this->seconds * 1000 + $this->minutes * 60 * 1000 + $this->hours * 60 * 60 * 1000 + $this->days * 24 * 60 * 60 * 1000;
    }

    public function getDefinition(): Definition
    {
        return new Definition(
            self::class,
            [
                $this->milliseconds,
                $this->seconds,
                $this->minutes,
                $this->hours,
                $this->days,
            ]
        );
    }
}
