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
