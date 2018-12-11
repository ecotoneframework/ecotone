<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Scheduling;

/**
 * Class UTCBasedClock
 * @package SimplyCodedSoftware\Messaging\Scheduling
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