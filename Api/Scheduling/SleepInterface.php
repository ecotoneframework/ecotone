<?php

declare(strict_types=1);

namespace Ecotone\Api\Scheduling;

/**
 * licence Apache-2.0
 */
interface SleepInterface
{
    public function sleep(Duration $duration): void;
}
