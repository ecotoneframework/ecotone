<?php

namespace Messaging\Support;

use Messaging\Channel\QueueChannel;
use Messaging\MessageHeaders;
use Messaging\MessagingTest;
use Messaging\Support\Clock\DumbClock;
use PHPUnit\Framework\TestCase;

/**
 * Class MessageBuilderTest
 * @package Messaging\Support
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageBuilderTest extends MessagingTest
{
    public function test_creating_from_payload()
    {
        $payload = 'somePayload';
        $headerName = 'token';
        $headerValue = 'abc';
        $message = MessageBuilder::withPayload($payload)
                    ->setHeader($headerName, $headerValue)
                    ->build();

        $this->assertEquals(
            $payload,
            $message->getPayload()
        );
        $this->assertEquals(
            $headerValue,
            $message->getHeaders()->get($headerName)
        );
    }

    public function test_setting_header_if_absent()
    {
        $headerName = 'new_header';
        $headerValue = '123';
        $message = MessageBuilder::withPayload('somePayload')
            ->setHeaderIfAbsent($headerName, $headerValue)
            ->setHeaderIfAbsent($headerName, 'x')
            ->build();

        $this->assertEquals(
            $message->getHeaders()->get($headerName),
            $headerValue
        );
    }

    public function test_removing_header_if_exists()
    {
        $headerName = 'new_header';
        $message = MessageBuilder::withPayload('somePayload')
            ->removeHeader($headerName)
            ->setHeaderIfAbsent($headerName, 'bla')
            ->removeHeader($headerName)
            ->build();

        $this->assertFalse($message->getHeaders()->containsKey($headerName));
    }

    public function test_setting_reply_channel_directly()
    {
        $replyChannel = QueueChannel::create();
        $message = MessageBuilder::withPayload('somePayload')
            ->setReplyChannel($replyChannel)
            ->build();

        $this->assertEquals(
            $replyChannel,
            $message->getHeaders()->get(MessageHeaders::REPLY_CHANNEL)
        );
    }

    public function test_setting_error_channel_directly()
    {
        $errorChannel = QueueChannel::create();
        $message = MessageBuilder::withPayload('somePayload')
            ->setErrorChannelName($errorChannel)
            ->build();

        $this->assertEquals(
            $errorChannel,
            $message->getHeaders()->get(MessageHeaders::ERROR_CHANNEL)
        );
    }

    public function test_creating_message_with_header()
    {
        $headerName = 'some';
        $message = MessageBuilder::withPayload('somePayload')
                    ->setHeader($headerName, new \stdClass())
                    ->build();

        $this->assertEquals(
            $message->getHeaders()->get($headerName),
            new \stdClass()
        );
    }

    public function test_creating_from_different_message()
    {
        $message = MessageBuilder::withPayload('somePayload')
            ->setHeader('some', new \stdClass())
            ->setHeader('token', 'johny')
            ->setReplyChannel(QueueChannel::create())
            ->build();

        $messageToCompare = MessageBuilder::fromMessage($message)
            ->build();
        $this->assertMessages(
            $message,
            $messageToCompare
        );
        $this->assertNotEquals(
            $message->getHeaders()->get(MessageHeaders::MESSAGE_ID),
            $messageToCompare->getHeaders()->get(MessageHeaders::MESSAGE_ID)
        );
    }
}