<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Scheduling;

use DateTime;
use DateTimeZone;

/**
 * Class StubClock
 * @package Ecotone\Messaging\Scheduling
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class StubUTCClock implements Clock
{
    private int $currentTime;

    /**
     * StubClock constructor.
     * @param int $currentTime
     */
    private function __construct(int $currentTime)
    {
        $this->currentTime = $currentTime;
    }

    /**
     * @param string $currentTime
     * @return StubUTCClock
     */
    public static function createWithCurrentTime(string $currentTime): self
    {
        return new self(self::createEpochTimeInMilliseconds($currentTime));
    }

    /**
     * @inheritDoc
     */
    public function unixTimeInMilliseconds(): int
    {
        return $this->currentTime;
    }

    public function sleep(int $seconds): void
    {
        $this->currentTime += $seconds * 1000;
    }

    public function usleep(int $microseconds): void
    {
        $this->currentTime += (int)round($microseconds / 1000);
    }

    /**
     * @param string $newCurrentTime
     * @return void
     */
    public function changeCurrentTime(string $newCurrentTime): void
    {
        $this->currentTime = self::createEpochTimeInMilliseconds($newCurrentTime);
    }

    /**
     * @param string $dateTimeAsString
     * @return int
     */
    public static function createEpochTimeFromDateTimeString(string $dateTimeAsString): int
    {
        return self::createEpochTimeInMilliseconds($dateTimeAsString);
    }

    /**
     * @param string $dateTimeAsString
     * @return int
     */
    private static function createEpochTimeInMilliseconds(string $dateTimeAsString): int
    {
        return (int)round((new DateTime($dateTimeAsString, new DateTimeZone('UTC')))->format('U') * 1000);
    }
}
