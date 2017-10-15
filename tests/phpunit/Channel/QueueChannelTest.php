<?php

namespace Messaging\Channel;

use Messaging\Support\MessageBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Class QueueChannelTest
 * @package Messaging\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class QueueChannelTest extends TestCase
{
    public function test_sending_and_receiving_message_in_last_in_first_out_order()
    {
        $queueChannel = new QueueChannel();

        $firstMessage = MessageBuilder::withPayload('a')->build();
        $secondMessage = MessageBuilder::withPayload('b')->build();

        $queueChannel->send($firstMessage);
        $queueChannel->send($secondMessage);

        $this->assertEquals(
            $secondMessage,
            $queueChannel->receive()
        );

        $this->assertEquals(
            $firstMessage,
            $queueChannel->receive()
        );
    }

    public function test_returning_null_when_queue_is_empty()
    {
        $queueChannel = new QueueChannel();

        $this->assertNull($queueChannel->receive());
    }
}