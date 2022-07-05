<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Scheduling;

/**
 * Class UTCBasedClock
 * @package Ecotone\Messaging\Scheduling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EpochBasedClock implements Clock
{
    /**
     * @inheritDoc
     */
    public function unixTimeInMilliseconds(): int
    {
        return (int)round(microtime(true) * 1000);
    }
}