<?php

namespace Test\Ecotone\Messaging\Unit\Channel;

use DateTimeImmutable;
use Ecotone\Messaging\Channel\DelayableQueueChannel;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Scheduling\DatePoint;
use Ecotone\Messaging\Scheduling\TimeSpan;
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
        $queueChannel = new DelayableQueueChannel('test');

        $firstMessage = MessageBuilder::withPayload('first')->build();
        $secondMessage = MessageBuilder::withPayload('second')->build();

        $queueChannel->send($firstMessage);
        $queueChannel->send($secondMessage);

        $this->assertEquals($firstMessage, $queueChannel->receive());
        $this->assertEquals($secondMessage, $queueChannel->receive());
    }

    public function test_returning_null_when_queue_is_empty()
    {
        $queueChannel = new DelayableQueueChannel('test');

        $this->assertNull($queueChannel->receive());
    }

    public function test_releasing_delayed_message()
    {
        $queueChannel = new DelayableQueueChannel('test');

        $message = MessageBuilder::withPayload('a')
                        ->setHeader(MessageHeaders::TIMESTAMP, (new DatePoint('2020-01-01 00:00:10.000'))->unixTime()->inSeconds())
                        ->setHeader(MessageHeaders::DELIVERY_DELAY, 10_000)
                        ->build();

        $queueChannel->send($message);

        $this->assertNull($queueChannel->receive());

        $queueChannel->releaseMessagesAwaitingFor(TimeSpan::withSeconds(9));

        $this->assertNull($queueChannel->receive());

        $queueChannel->releaseMessagesAwaitingFor(TimeSpan::withSeconds(10));

        $this->assertEquals(
            MessageBuilder::fromMessage($message)
                ->removeHeader(MessageHeaders::DELIVERY_DELAY)
                ->build(),
            $queueChannel->receive()
        );
    }

    public function test_releasing_delayed_message_in_order()
    {
        $queueChannel = new DelayableQueueChannel('test');

        $firstMessage = MessageBuilder::withPayload('first')
            ->setHeader(MessageHeaders::TIMESTAMP, (new DatePoint('2020-01-01 00:00:10.000'))->unixTime()->inSeconds())
            ->setHeader(MessageHeaders::DELIVERY_DELAY, 3000)
            ->build();

        $secondMessage = MessageBuilder::withPayload('second')
            ->setHeader(MessageHeaders::TIMESTAMP, (new DatePoint('2020-01-01 00:00:10.000'))->unixTime()->inSeconds())
            ->setHeader(MessageHeaders::DELIVERY_DELAY, 2000)
            ->build();

        $thirdMessage = MessageBuilder::withPayload('third')
            ->setHeader(MessageHeaders::TIMESTAMP, (new DatePoint('2020-01-01 00:00:10.000'))->unixTime()->inSeconds())
            ->setHeader(MessageHeaders::DELIVERY_DELAY, 1000)
            ->build();

        $queueChannel->send($firstMessage);
        $queueChannel->send($secondMessage);
        $queueChannel->send($thirdMessage);

        $queueChannel->releaseMessagesAwaitingFor(TimeSpan::withSeconds(1));
        $this->assertEquals(
            MessageBuilder::fromMessage($thirdMessage)
                ->removeHeader(MessageHeaders::DELIVERY_DELAY)
                ->build(),
            $queueChannel->receive()
        );

        $queueChannel->releaseMessagesAwaitingFor(TimeSpan::withSeconds(2));
        $this->assertEquals(
            MessageBuilder::fromMessage($secondMessage)
                ->removeHeader(MessageHeaders::DELIVERY_DELAY)
                ->build(),
            $queueChannel->receive()
        );

        $queueChannel->releaseMessagesAwaitingFor(TimeSpan::withSeconds(3));
        $this->assertEquals(
            MessageBuilder::fromMessage($firstMessage)
                ->removeHeader(MessageHeaders::DELIVERY_DELAY)
                ->build(),
            $queueChannel->receive()
        );
    }

    public function test_releasing_delayed_message_in_order_when_have_same_delay()
    {
        $queueChannel = new DelayableQueueChannel('test');

        $firstMessage = MessageBuilder::withPayload('first')
            ->setHeader(MessageHeaders::TIMESTAMP, (new DatePoint('2020-01-01 00:00:10.000'))->unixTime()->inSeconds())
            ->setHeader(MessageHeaders::DELIVERY_DELAY, 1000)
            ->build();

        $secondMessage = MessageBuilder::withPayload('second')
            ->setHeader(MessageHeaders::TIMESTAMP, (new DatePoint('2020-01-01 00:00:10.000'))->unixTime()->inSeconds())
            ->setHeader(MessageHeaders::DELIVERY_DELAY, 2000)
            ->build();

        $thirdMessage = MessageBuilder::withPayload('third')
            ->setHeader(MessageHeaders::TIMESTAMP, (new DatePoint('2020-01-01 00:00:10.000'))->unixTime()->inSeconds())
            ->setHeader(MessageHeaders::DELIVERY_DELAY, 1000)
            ->build();

        $queueChannel->send($firstMessage);
        $queueChannel->send($secondMessage);
        $queueChannel->send($thirdMessage);

        $queueChannel->releaseMessagesAwaitingFor(TimeSpan::withSeconds(1));
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

        $queueChannel->releaseMessagesAwaitingFor(TimeSpan::withSeconds(2));
        $this->assertEquals(
            MessageBuilder::fromMessage($secondMessage)
                ->removeHeader(MessageHeaders::DELIVERY_DELAY)
                ->build(),
            $queueChannel->receive()
        );
    }

    public function test_releasing_delayed_based_on_datetime()
    {
        $queueChannel = new DelayableQueueChannel('test');

        $firstMessage = MessageBuilder::withPayload('first')
            ->setHeader(MessageHeaders::TIMESTAMP, (new DatePoint('2020-01-01 00:00:10.000'))->unixTime()->inSeconds())
            ->setHeader(MessageHeaders::DELIVERY_DELAY, 1000)
            ->build();

        $secondMessage = MessageBuilder::withPayload('second')
            ->setHeader(MessageHeaders::TIMESTAMP, (new DatePoint('2020-01-01 00:00:10.000'))->unixTime()->inSeconds())
            ->setHeader(MessageHeaders::DELIVERY_DELAY, 2000)
            ->build();

        $thirdMessage = MessageBuilder::withPayload('third')
            ->setHeader(MessageHeaders::TIMESTAMP, (new DatePoint('2020-01-01 00:00:10.000'))->unixTime()->inSeconds())
            ->setHeader(MessageHeaders::DELIVERY_DELAY, 1000)
            ->build();

        $queueChannel->send($firstMessage);
        $queueChannel->send($secondMessage);
        $queueChannel->send($thirdMessage);

        $queueChannel->releaseMessagesAwaitingFor(new DateTimeImmutable('2020-01-01 00:00:11.000'));
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

        $queueChannel->releaseMessagesAwaitingFor(new DateTimeImmutable('2020-01-01 00:00:12.000'));
        $this->assertEquals(
            MessageBuilder::fromMessage($secondMessage)
                ->removeHeader(MessageHeaders::DELIVERY_DELAY)
                ->build(),
            $queueChannel->receive()
        );
    }
}
