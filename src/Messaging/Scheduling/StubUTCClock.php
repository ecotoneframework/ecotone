<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Scheduling;

/**
 * Class StubClock
 * @package Ecotone\Messaging\Scheduling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
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
    public static function createWithCurrentTime(string $currentTime) : self
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

    /**
     * @param string $newCurrentTime
     * @return void
     */
    public function changeCurrentTime(string $newCurrentTime) : void
    {
        $this->currentTime = self::createEpochTimeInMilliseconds($newCurrentTime);
    }

    /**
     * @param string $dateTimeAsString
     * @return int
     */
    public static function createEpochTimeFromDateTimeString(string $dateTimeAsString) : int
    {
        return self::createEpochTimeInMilliseconds($dateTimeAsString);
    }

    /**
     * @param string $dateTimeAsString
     * @return int
     */
    private static function createEpochTimeInMilliseconds(string $dateTimeAsString): int
    {
        return (int)round((new \DateTime($dateTimeAsString, new \DateTimeZone('UTC')))->format('U') * 1000);
    }
}