<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Scheduling;

use DateTime;
use DateTimeInterface;
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
    public function __construct(
        private ?int $currentTime = null
    ) {

    }

    /**
     * @param string $currentTime
     * @return StubUTCClock
     */
    public static function createWithCurrentTime(string $currentTime): self
    {
        return new self(self::createEpochTimeInMilliseconds($currentTime));
    }

    public static function createNow(): self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function unixTimeInMilliseconds(): int
    {
        return $this->currentTime !== null ? $this->currentTime : self::createEpochTimeInMilliseconds('now');
    }

    public function sleep(int $seconds): void
    {
        $this->currentTime += $seconds * 1000;
    }

    public function usleep(int $microseconds): void
    {
        $this->currentTime += (int)round($microseconds / 1000);
    }

    public function changeCurrentTime(string|DateTimeInterface $newCurrentTime): void
    {
        $this->currentTime = self::createEpochTimeInMilliseconds($newCurrentTime);
    }

    public function changeCurrentTimeWithMillisecondsTimestamp(int $milliseconds): void
    {
        $this->currentTime = $milliseconds;
    }

    /**
     * @param string $dateTimeAsString
     * @return int
     */
    public static function createEpochTimeFromDateTimeString(string $dateTimeAsString): int
    {
        return self::createEpochTimeInMilliseconds($dateTimeAsString);
    }

    public function isTimeAlreadyChanged(): bool
    {
        return $this->currentTime !== null;
    }

    private static function createEpochTimeInMilliseconds(string|DateTimeInterface $dateTime): int
    {
        if ($dateTime === 'now') {
            return EpochBasedClock::getCurrentTimeInMilliseconds();
        }

        return EpochBasedClock::getTimestampWithMillisecondsFor(
            is_string($dateTime) ? new DateTime($dateTime, new DateTimeZone('UTC')) : $dateTime
        );
    }
}
