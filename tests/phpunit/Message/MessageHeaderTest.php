<?php

namespace Messaging\Message;

use Messaging\Exception\Message\InvalidMessageHeaderException;
use Messaging\Registry\DumbClock;
use Messaging\Registry\DumbUuidGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Class MessageHeaderTest
 * @package Messaging\Message
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageHeaderTest extends TestCase
{
    public function test_creating_with_generated_headers()
    {
        $messageId = '2d996892-72c7-40ba-b20b-ccdd07221167';
        $timestamp = 1000;

        $this->assertEquals([
            MessageHeaders::MESSAGE_ID => $messageId,
            MessageHeaders::MESSAGE_CORRELATION_ID => $messageId,
            MessageHeaders::TIMESTAMP => $timestamp
        ], MessageHeaders::createEmpty(DumbUuidGenerator::create($messageId), DumbClock::create($timestamp))->headers());
    }

    public function test_throwing_exception_if_creating_with_empty_header()
    {
        $this->expectException(InvalidMessageHeaderException::class);

        MessageHeaders::createWith([
           '' => 'value'
        ]);
    }

    public function test_throwing_exception_if_header_value_is_object()
    {
        $this->expectException(InvalidMessageHeaderException::class);

        MessageHeaders::createWith([
            "name" => new \stdClass()
        ]);
    }
}