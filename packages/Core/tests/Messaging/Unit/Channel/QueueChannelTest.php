<?php

namespace Test\Ecotone\Messaging\Unit\Channel;

use PHPUnit\Framework\TestCase;
use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class QueueChannelTest
 * @package Ecotone\Messaging\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class QueueChannelTest extends TestCase
{
    public function test_sending_and_receiving_message_in_last_in_first_out_order()
    {
        $queueChannel = QueueChannel::create();

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
        $queueChannel = QueueChannel::create();

        $this->assertNull($queueChannel->receive());
    }
}