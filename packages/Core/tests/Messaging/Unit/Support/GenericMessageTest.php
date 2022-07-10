<?php

namespace Test\Ecotone\Messaging\Unit\Support;
use Ecotone\Messaging\Support\GenericMessage;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Test\Ecotone\Messaging\Unit\MessagingTest;


/**
 * Class GenericMessageTest
 * @package Ecotone\Messaging\Support
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GenericMessageTest extends MessagingTest
{
    public function test_creating_generic_message_with_headers_as_key_value()
    {
        $payload = '{"name": "johny"}';
        $headerName = "token";
        $headerValue = '123';

        $message = GenericMessage::createWithArrayHeaders($payload, [$headerName => $headerValue]);

        $this->assertEquals(
            $headerValue,
            $message->getHeaders()->get($headerName)
        );
        $this->assertEquals($payload, $message->getPayload());
    }

    public function test_creating_without_headers()
    {
        $payload = 'somePayload';
        $message = GenericMessage::createWithEmptyHeaders($payload);

        $this->assertEquals(
            $message->getPayload(),
            $payload
        );
    }

    public function test_throwing_exception_if_payload_is_null()
    {
        $this->expectException(InvalidArgumentException::class);

        GenericMessage::createWithEmptyHeaders(null);
    }
}