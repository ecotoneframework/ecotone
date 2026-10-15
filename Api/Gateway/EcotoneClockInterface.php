<?php

declare(strict_types=1);

namespace Ecotone\Api\Gateway;

use Ecotone\Api\Scheduling\DatePoint;
use Ecotone\Api\Scheduling\SleepInterface;
use Psr\Clock\ClockInterface as PsrClockInterface;

/**
 * licence Apache-2.0
 */
interface EcotoneClockInterface extends PsrClockInterface, SleepInterface
{
    public function now(): DatePoint;
}
