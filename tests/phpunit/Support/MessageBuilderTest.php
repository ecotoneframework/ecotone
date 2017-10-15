<?php

namespace Messaging\Support;

use Messaging\MessageHeaders;
use Messaging\Support\Clock\DumbClock;
use PHPUnit\Framework\TestCase;

/**
 * Class MessageBuilderTest
 * @package Messaging\Support
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageBuilderTest extends TestCase
{
    public function test_creating_from_payload()
    {
        $payload = 'somePayload';
        $currentTimestamp = 3000;
        $headerName = 'token';
        $headerValue = 'abc';
        $message = MessageBuilder::withPayload($payload)
                    ->setClock(DumbClock::create($currentTimestamp))
                    ->setHeader($headerName, $headerValue)
                    ->build();

        $this->assertEquals(
            $currentTimestamp,
            $message->getHeaders()->get(MessageHeaders::TIMESTAMP)
        );
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
        $replyChannel = 'some_reply_channel';
        $message = MessageBuilder::withPayload('somePayload')
            ->setReplyChannelName($replyChannel)
            ->build();

        $this->assertEquals(
            $replyChannel,
            $message->getHeaders()->get(MessageHeaders::REPLY_CHANNEL)
        );
    }

    public function test_setting_error_channel_directly()
    {
        $errorChannel = 'some_error_channel';
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
}