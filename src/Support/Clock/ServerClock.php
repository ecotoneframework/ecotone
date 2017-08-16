<?php

namespace Messaging\Support\Clock;

use Messaging\Support\Clock;

/**
 * Class ServerClock
 * @package Messaging\Support\Clock
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServerClock implements Clock
{
    private function __construct()
    {
    }

    /**
     * @return ServerClock
     */
    public static function create() : self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function getCurrentTimestamp(): int
    {
        return time();
    }
}