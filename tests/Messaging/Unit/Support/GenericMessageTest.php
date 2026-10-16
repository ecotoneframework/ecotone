<?php

namespace Test\Ecotone\Messaging\Unit\Support;

use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\GenericMessage;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Test\Ecotone\Messaging\Unit\MessagingTestCase;

/**
 * Class GenericMessageTest
 * @package Ecotone\Messaging\Support
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class GenericMessageTest extends MessagingTestCase
{
    public function test_creating_generic_message_with_headers_as_key_value()
    {
        $payload = '{"name": "johny"}';
        $headerName = 'token';
        $headerValue = '123';

        $message = GenericMessage::createWithArrayHeaders($payload, [MessageHeaders::TIMESTAMP => 1700000000, $headerName => $headerValue]);

        $this->assertEquals(
            $headerValue,
            $message->getHeaders()->get($headerName)
        );
        $this->assertEquals($payload, $message->getPayload());
    }

    public function test_creating_without_headers()
    {
        $payload = 'somePayload';
        $message = GenericMessage::createWithArrayHeaders($payload, [MessageHeaders::TIMESTAMP => 1700000000]);

        $this->assertEquals(
            $message->getPayload(),
            $payload
        );
    }

    public function test_throwing_exception_if_payload_is_null()
    {
        $this->expectException(InvalidArgumentException::class);

        GenericMessage::createWithArrayHeaders(null, [MessageHeaders::TIMESTAMP => 1700000000]);
    }
}
