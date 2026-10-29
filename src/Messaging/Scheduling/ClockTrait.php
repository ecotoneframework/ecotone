<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Messaging\Scheduling;

trait ClockTrait
{
    abstract public function usleep(int $microseconds): void;

    public function sleep(Duration $duration): void
    {
        if ($duration->isNegativeOrZero()) {
            return;
        }
        $this->usleep($duration->inMicroseconds());
    }
}
