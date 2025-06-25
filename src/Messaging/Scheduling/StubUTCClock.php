<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Scheduling;

use DateTimeInterface;
use DateTimeZone;

/**
 * @package Ecotone\Messaging\Scheduling
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class StubUTCClock implements EcotoneClockInterface
{
    public function __construct(
        private DatePoint $currentTime = new DatePoint('now', new DateTimeZone('UTC'))
    ) {
    }

    /**
     * @param string $currentTime
     * @return StubUTCClock
     */
    public static function createWithCurrentTime(string $currentTime): self
    {
        return new self(new DatePoint($currentTime, new DateTimeZone('UTC')));
    }

    public function changeCurrentTime(string|DateTimeInterface $newCurrentTime): void
    {
        $this->currentTime = new DatePoint($newCurrentTime);
    }

    public function now(): DatePoint
    {
        return $this->currentTime;
    }

    public function sleep(Duration $duration): void
    {
        $this->currentTime = $this->currentTime->add($duration->zeroIfNegative());
    }
}
