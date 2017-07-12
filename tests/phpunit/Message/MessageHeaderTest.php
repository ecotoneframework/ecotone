<?php

namespace Messaging\Message;

use Messaging\Exception\Message\InvalidMessageHeaderException;
use Messaging\Exception\Message\MessageHeaderDoesNotExistsException;
use Messaging\Registry\DumbClock;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

/**
 * Class MessageHeaderTest
 * @package Messaging\Message
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageHeaderTest extends TestCase
{
    public function test_creating_with_generated_headers()
    {
        $timestamp = 1000;

        $headers = MessageHeaders::createEmpty(DumbClock::create($timestamp));

        $this->assertEquals(3, $headers->size());
        $this->assertTrue($headers->containsKey(MessageHeaders::MESSAGE_CORRELATION_ID));
        $this->assertTrue(Uuid::isValid($headers->get(MessageHeaders::MESSAGE_ID)));
        $this->assertTrue(Uuid::isValid($headers->get(MessageHeaders::MESSAGE_CORRELATION_ID)));
        $this->assertEquals($timestamp, $headers->get(MessageHeaders::TIMESTAMP));
        $this->assertEquals(NullableMessageChannel::CHANNEL_NAME, $headers->getReplyChannel());
        $this->assertEquals(NullableMessageChannel::CHANNEL_NAME, $headers->getErrorChannel());
    }

    public function test_throwing_exception_if_creating_with_empty_header()
    {
        $this->expectException(InvalidMessageHeaderException::class);

        $currentTimestamp = 20;
        $headers = [
            '' => 'value'
        ];

        $this->createWithCustomHeaders($currentTimestamp, $headers);
    }

    public function test_throwing_exception_if_header_value_is_object()
    {
        $this->expectException(InvalidMessageHeaderException::class);

        $currentTimestamp = 20;
        $headers = [
            "name" => new \stdClass()
        ];

        $this->createWithCustomHeaders($currentTimestamp, $headers);
    }

    public function test_creating_with_extra_headers()
    {
        $currentTimestamp = 20;
        $headers = [
            'key' => 'value'
        ];
        $messageHeaders = $this->createWithCustomHeaders($currentTimestamp, $headers);

        $this->assertEquals(4, $messageHeaders->size());
    }

    public function test_creating_with_custom_headers_and_timestamp()
    {
        $currentTimestamp = 20;
        $headers = [
            'key' => 'value'
        ];
        $messageHeaders = MessageHeaders::createWithCustomHeadersAndTimestamp($currentTimestamp, $headers);

        $this->assertEquals(4, $messageHeaders->size());
        $this->assertEquals($currentTimestamp, $messageHeaders->get(MessageHeaders::TIMESTAMP));
    }

    public function test_throwing_exception_is_asking_for_not_existing_header()
    {
        $messageHeaders = MessageHeaders::createWithTimestamp(1500);

        $this->expectException(MessageHeaderDoesNotExistsException::class);

        $messageHeaders->get('some');
    }

    public function test_if_contains_value()
    {
        $messageHeaders = MessageHeaders::createWithTimestamp(100);

        $this->assertTrue($messageHeaders->containsValue(100));
        $this->assertFalse($messageHeaders->containsValue('test'));
    }

    public function test_checking_equality()
    {
        $messageHeaders = MessageHeaders::createWithTimestamp(100);
        $this->assertFalse(MessageHeaders::createWithTimestamp(100)->equals($messageHeaders));
        $this->assertTrue($messageHeaders->equals($messageHeaders));
    }

    public function test_not_allowing_to_replace_message_id()
    {
        $messageHeaders = MessageHeaders::createWithCustomHeaders(DumbClock::create(100), [
            MessageHeaders::MESSAGE_ID => 'cb04d8f7-006e-4c23-baa2-8b75cde8f3c1'
        ]);

        $this->assertNotEquals('cb04d8f7-006e-4c23-baa2-8b75cde8f3c1', $messageHeaders->get(MessageHeaders::MESSAGE_ID));
    }

    public function test_creating_correlated_message_headers_from_old_message_headers()
    {
        $oldMessageHeaders = MessageHeaders::createWithTimestamp(1000);
        $newMessageHeaders = MessageHeaders::createWithCorrelated(DumbClock::create(1001), [], $oldMessageHeaders);

        $this->assertEquals($oldMessageHeaders->get(MessageHeaders::MESSAGE_CORRELATION_ID), $newMessageHeaders->get(MessageHeaders::MESSAGE_CORRELATION_ID));
    }

    public function test_creating_with_causation_message()
    {
        $oldMessageHeaders = MessageHeaders::createWithTimestamp(1000);
        $newMessageHeaders = MessageHeaders::createWithCausation(DumbClock::create(1001), [], $oldMessageHeaders);

        $this->assertEquals($oldMessageHeaders->get(MessageHeaders::MESSAGE_CORRELATION_ID), $newMessageHeaders->get(MessageHeaders::MESSAGE_CORRELATION_ID));
        $this->assertEquals($oldMessageHeaders->get(MessageHeaders::MESSAGE_ID), $newMessageHeaders->get(MessageHeaders::CAUSATION_MESSAGE_ID));
    }

    public function test_creating_with_channels()
    {
        $replyChannel = 'replyCh';
        $errorChannel = 'errorCh';
        $oldMessageHeaders = MessageHeaders::createWithCustomHeadersAndTimestamp(1000, [
            MessageHeaders::REPLY_CHANNEL => $replyChannel,
            MessageHeaders::ERROR_CHANNEL => $errorChannel
        ]);

        $this->assertEquals($replyChannel, $oldMessageHeaders->getReplyChannel());
        $this->assertEquals($errorChannel, $oldMessageHeaders->getErrorChannel());
    }
    
    /**
     * @param $currentTimestamp
     * @param $headers
     * @return MessageHeaders
     */
    private function createWithCustomHeaders($currentTimestamp, $headers): MessageHeaders
    {
        $messageHeaders = MessageHeaders::createWithCustomHeaders(DumbClock::create($currentTimestamp), $headers);
        return $messageHeaders;
    }
}