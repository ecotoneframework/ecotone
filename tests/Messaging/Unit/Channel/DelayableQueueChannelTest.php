<?php

namespace Test\Ecotone\Messaging\Unit\Channel;

use Ecotone\Messaging\Channel\DelayableQueueChannel;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class DelayableQueueChannelTest extends TestCase
{
    public function test_sending_and_receiving_message_in_last_in_first_out_order()
    {
        $queueChannel = DelayableQueueChannel::create();

        $firstMessage = MessageBuilder::withPayload('first')->build();
        $secondMessage = MessageBuilder::withPayload('second')->build();

        $queueChannel->send($firstMessage);
        $queueChannel->send($secondMessage);

        $this->assertEquals($firstMessage, $queueChannel->receive());
        $this->assertEquals($secondMessage, $queueChannel->receive());
    }

    public function test_returning_null_when_queue_is_empty()
    {
        $queueChannel = DelayableQueueChannel::create();

        $this->assertNull($queueChannel->receive());
    }

    public function test_releasing_delayed_message()
    {
        $queueChannel = DelayableQueueChannel::create();

        $message = MessageBuilder::withPayload('a')
                        ->setHeader(MessageHeaders::DELIVERY_DELAY, 100)
                        ->build();

        $queueChannel->send($message);

        $this->assertNull($queueChannel->receive());

        $queueChannel->releaseMessagesAwaitingFor(99);

        $this->assertNull($queueChannel->receive());

        $queueChannel->releaseMessagesAwaitingFor(100);

        $this->assertEquals(
            MessageBuilder::fromMessage($message)
                ->removeHeader(MessageHeaders::DELIVERY_DELAY)
                ->build(),
            $queueChannel->receive()
        );
    }

    public function test_releasing_delayed_message_in_order()
    {
        $queueChannel = DelayableQueueChannel::create();

        $firstMessage = MessageBuilder::withPayload('first')
            ->setHeader(MessageHeaders::DELIVERY_DELAY, 100)
            ->build();

        $secondMessage = MessageBuilder::withPayload('second')
            ->setHeader(MessageHeaders::DELIVERY_DELAY, 99)
            ->build();

        $thirdMessage = MessageBuilder::withPayload('third')
            ->setHeader(MessageHeaders::DELIVERY_DELAY, 98)
            ->build();

        $queueChannel->send($firstMessage);
        $queueChannel->send($secondMessage);
        $queueChannel->send($thirdMessage);

        $queueChannel->releaseMessagesAwaitingFor(98);
        $this->assertEquals(
            MessageBuilder::fromMessage($thirdMessage)
                ->removeHeader(MessageHeaders::DELIVERY_DELAY)
                ->build(),
            $queueChannel->receive()
        );

        $queueChannel->releaseMessagesAwaitingFor(99);
        $this->assertEquals(
            MessageBuilder::fromMessage($secondMessage)
                ->removeHeader(MessageHeaders::DELIVERY_DELAY)
                ->build(),
            $queueChannel->receive()
        );

        $queueChannel->releaseMessagesAwaitingFor(100);
        $this->assertEquals(
            MessageBuilder::fromMessage($firstMessage)
                ->removeHeader(MessageHeaders::DELIVERY_DELAY)
                ->build(),
            $queueChannel->receive()
        );
    }

    public function test_releasing_delayed_message_in_order_when_have_same_delay()
    {
        $queueChannel = DelayableQueueChannel::create();

        $firstMessage = MessageBuilder::withPayload('first')
            ->setHeader(MessageHeaders::DELIVERY_DELAY, 99)
            ->build();

        $secondMessage = MessageBuilder::withPayload('second')
            ->setHeader(MessageHeaders::DELIVERY_DELAY, 100)
            ->build();

        $thirdMessage = MessageBuilder::withPayload('third')
            ->setHeader(MessageHeaders::DELIVERY_DELAY, 99)
            ->build();

        $queueChannel->send($firstMessage);
        $queueChannel->send($secondMessage);
        $queueChannel->send($thirdMessage);

        $queueChannel->releaseMessagesAwaitingFor(99);
        $this->assertEquals(
            MessageBuilder::fromMessage($firstMessage)
                ->removeHeader(MessageHeaders::DELIVERY_DELAY)
                ->build(),
            $queueChannel->receive()
        );

        $this->assertEquals(
            MessageBuilder::fromMessage($thirdMessage)
                ->removeHeader(MessageHeaders::DELIVERY_DELAY)
                ->build(),
            $queueChannel->receive()
        );

        $queueChannel->releaseMessagesAwaitingFor(100);
        $this->assertEquals(
            MessageBuilder::fromMessage($secondMessage)
                ->removeHeader(MessageHeaders::DELIVERY_DELAY)
                ->build(),
            $queueChannel->receive()
        );
    }
}
