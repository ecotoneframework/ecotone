<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Support;
use SimplyCodedSoftware\IntegrationMessaging\Support\GenericMessage;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;


/**
 * Class GenericMessageTest
 * @package SimplyCodedSoftware\IntegrationMessaging\Support
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
}