<?php

namespace Messaging;

use Messaging\InvalidMessageHeaderException;
use Messaging\MessageHeaderDoesNotExistsException;
use Messaging\Support\Clock\DumbClock;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

/**
 * Class MessageHeaderTest
 * @package Messaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageHeadersTest extends TestCase
{
    public function test_creating_with_generated_headers()
    {
        $timestamp = 1000;

        $headers = MessageHeaders::createEmpty($timestamp);

        $this->assertEquals(2, $headers->size());
        $this->assertTrue(Uuid::isValid($headers->get(MessageHeaders::MESSAGE_ID)));
        $this->assertEquals($timestamp, $headers->get(MessageHeaders::TIMESTAMP));
        $this->assertEquals(NullableMessageChannel::CHANNEL_NAME, $headers->getReplyChannel());
        $this->assertEquals(NullableMessageChannel::CHANNEL_NAME, $headers->getErrorChannel());

        $this->assertFalse($headers->hasMessageId('2db4db21-e3f1-492a-af98-a61468bb03e9'));
        $this->assertTrue($headers->hasMessageId($headers->get(MessageHeaders::MESSAGE_ID)));
    }

    public function test_creating_with_custom_headers_and_timestamp()
    {
        $currentTimestamp = 20;
        $headers = [
            'key' => 'value'
        ];
        $messageHeaders = MessageHeaders::create($currentTimestamp, $headers);

        $this->assertEquals(3, $messageHeaders->size());
        $this->assertEquals($currentTimestamp, $messageHeaders->get(MessageHeaders::TIMESTAMP));
    }

    public function test_throwing_exception_when_asking_for_not_existing_header()
    {
        $messageHeaders = MessageHeaders::createEmpty(1500);

        $this->expectException(MessageHeaderDoesNotExistsException::class);

        $messageHeaders->get('some');
    }

    public function test_if_contains_value()
    {
        $messageHeaders = MessageHeaders::createEmpty(100);

        $this->assertTrue($messageHeaders->containsValue(100));
        $this->assertFalse($messageHeaders->containsValue('test'));
    }

    public function test_checking_equality()
    {
        $messageHeaders = MessageHeaders::createEmpty(100);
        $this->assertFalse(MessageHeaders::createEmpty(100)->equals($messageHeaders));
        $this->assertTrue($messageHeaders->equals($messageHeaders));
    }

    public function test_creating_with_channels()
    {
        $replyChannel = 'replyCh';
        $errorChannel = 'errorCh';
        $oldMessageHeaders = MessageHeaders::create(1000, [
            MessageHeaders::REPLY_CHANNEL => $replyChannel,
            MessageHeaders::ERROR_CHANNEL => $errorChannel
        ]);

        $this->assertEquals($replyChannel, $oldMessageHeaders->getReplyChannel());
        $this->assertEquals($errorChannel, $oldMessageHeaders->getErrorChannel());
    }

    public function test_creating_with_object_as_value()
    {
        $messageHeader = "some";
        $messageHeaders = MessageHeaders::create(0, [
            $messageHeader => new \stdClass()
        ]);

        $this->assertEquals(
            $messageHeaders->get($messageHeader),
            new \stdClass()
        );
    }
}