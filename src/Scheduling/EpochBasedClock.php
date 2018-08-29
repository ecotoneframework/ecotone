<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Scheduling;

/**
 * Class UTCBasedClock
 * @package SimplyCodedSoftware\IntegrationMessaging\Scheduling
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