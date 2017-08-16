<?php

namespace Messaging\Support\Clock;

use Messaging\Support\Clock;

/**
 * Class DumbClock
 * @package Messaging\Registry
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DumbClock implements Clock
{
    /**
     * @var int
     */
    private $currentTimestamp;

    /**
     * DumbClock constructor.
     * @param int $currentTimestamp
     */
    private function __construct(int $currentTimestamp)
    {
        $this->currentTimestamp = $currentTimestamp;
    }

    /**
     * @param int $currentTimestamp
     * @return DumbClock
     */
    public static function create(int $currentTimestamp) : self
    {
        return new self($currentTimestamp);
    }

    /**
     * @inheritDoc
     */
    public function getCurrentTimestamp(): int
    {
        return $this->currentTimestamp;
    }
}