<?php

namespace Messaging\Support\Clock;

use PHPUnit\Framework\TestCase;

/**
 * Class ServerClockTest
 * @package Messaging\Support\Clock
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServerClockTest extends TestCase
{
    public function test_generating_timestamp()
    {
        $serverClock = ServerClock::create();

        $this->assertNotNull($serverClock->getCurrentTimestamp());
    }
}