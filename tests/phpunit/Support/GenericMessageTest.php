<?php

namespace Messaging\Support;
use Messaging\Message;
use Messaging\Support\Clock\DumbClock;
use PHPUnit\Framework\TestCase;

/**
 * Class GenericMessageTest
 * @package Messaging\Support
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GenericMessageTest extends TestCase
{
    public function test_creating_generic_message_with_headers_as_key_value()
    {
        $payload = '{"name": "johny"}';
        $headerName = "token";
        $headerValue = '123';

        $message = GenericMessage::createWithArrayHeaders(DumbClock::create(1000), $payload, [$headerName => $headerValue]);

        $this->assertEquals(
            $headerValue,
            $message->getHeaders()->get($headerName)
        );
        $this->assertEquals($payload, $message->getPayload());
    }

    public function test_creating_without_headers()
    {
        $payload = 'somePayload';
        $message = GenericMessage::createWithEmptyHeaders(DumbClock::create(1000), $payload);

        $this->assertEquals(
            $message->getPayload(),
            $payload
        );
    }
}