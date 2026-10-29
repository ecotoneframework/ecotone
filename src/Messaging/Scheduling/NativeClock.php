<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Scheduling;

use function usleep;

/**
 * Class UTCBasedClock
 * @package Ecotone\Messaging\Scheduling
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class NativeClock implements EcotoneClockInterface
{
    /**
     * @inheritDoc
     */
    public function now(): DatePoint
    {
        return new DatePoint('now');
    }

    /**
     * @inheritDoc
     */
    public function sleep(Duration $duration): void
    {
        if ($duration->isNegativeOrZero()) {
            return;
        }

        usleep($duration->inMicroseconds());
    }
}
