<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Scheduling;

use DateTimeInterface;

/**
 * Class UTCBasedClock
 * @package Ecotone\Messaging\Scheduling
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class EpochBasedClock implements Clock
{
    /**
     * @inheritDoc
     */
    public function unixTimeInMilliseconds(): int
    {
        return $this->getCurrentTimeInMilliseconds();
    }

    public static function getCurrentTimeInMilliseconds(): int
    {
        return (int)round(microtime(true) * 1000);
    }

    public static function getTimestampWithMillisecondsFor(DateTimeInterface $dateTime): int
    {
        return (int)round($dateTime->format('U.u') * 1000);
    }

    public static function getTimestampFor(DateTimeInterface $dateTime): int
    {
        return (int)$dateTime->format('U');
    }
}
