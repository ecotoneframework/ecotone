<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Support;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;

use function json_decode;

use stdClass;
use Test\Ecotone\Messaging\Unit\MessagingTestCase;

/**
 * Class MessageBuilderTest
 * @package Ecotone\Messaging\Support
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class MessageBuilderTest extends MessagingTestCase
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
            ->setErrorChannel($errorChannel)
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
                    ->setHeader($headerName, new stdClass())
                    ->build();

        $this->assertEquals(
            $message->getHeaders()->get($headerName),
            new stdClass()
        );
    }

    public function test_creating_from_different_message()
    {
        $message = MessageBuilder::withPayload('somePayload')
            ->setHeader('some', new stdClass())
            ->setHeader('token', 'johny')
            ->setHeader(MessageHeaders::TIMESTAMP, 1587658787863)
            ->setReplyChannel(QueueChannel::create())
            ->build();

        $messageToCompare = MessageBuilder::fromMessage($message)
            ->build();
        $this->assertMessages(
            $message,
            $messageToCompare
        );

        $this->assertEquals(
            $message->getHeaders()->get(MessageHeaders::MESSAGE_ID),
            $messageToCompare->getHeaders()->get(MessageHeaders::MESSAGE_ID)
        );
        $this->assertEquals(
            $message->getHeaders()->get(MessageHeaders::TIMESTAMP),
            $messageToCompare->getHeaders()->get(MessageHeaders::TIMESTAMP)
        );
    }

    public function test_creating_with_empty_array_as_payload()
    {
        $message = MessageBuilder::withPayload([])
                    ->build();

        $this->assertEquals([], $message->getPayload());
    }

    public function test_converting_to_string()
    {
        $this->assertEquals(
            'some',
            json_decode(
                (string)MessageBuilder::withPayload('some')
                    ->build(),
                true
            )['payload']
        );
    }

    public function test_allow_to_manually_message_header_id_and_timestamp()
    {
        $this->assertEquals(
            MessageBuilder::withPayload('some')
                ->setHeader(MessageHeaders::MESSAGE_ID, 123)
                ->setHeader(MessageHeaders::TIMESTAMP, 1587658787863)
                ->setHeader(MessageHeaders::MESSAGE_CORRELATION_ID, 1234)
                ->build(),
            MessageBuilder::withPayload('some')
                ->setMultipleHeaders([
                    MessageHeaders::MESSAGE_ID => 123,
                    MessageHeaders::TIMESTAMP => 1587658787863,
                    MessageHeaders::MESSAGE_CORRELATION_ID => 1234,
                ])
                ->build()
        );
    }

    public function test_correlation_id_is_preserved()
    {
        $correlationId = 1587658787863;
        $message = MessageBuilder::withPayload('some')
            ->setHeader(MessageHeaders::MESSAGE_CORRELATION_ID, $correlationId)
            ->build();

        $this->assertEquals(
            $correlationId,
            MessageBuilder::fromMessage($message)->build()->getHeaders()->get(MessageHeaders::MESSAGE_CORRELATION_ID)
        );
    }

    public function test_correlation_id_is_preserved_when_id_is_generated()
    {
        $correlationId = 1587658787863;
        $message = MessageBuilder::withPayload('some')
            ->setHeader(MessageHeaders::MESSAGE_CORRELATION_ID, $correlationId)
            ->build();

        $this->assertEquals(
            $correlationId,
            MessageBuilder::fromParentMessage($message)->build()->getHeaders()->get(MessageHeaders::MESSAGE_CORRELATION_ID)
        );
    }

    public function test_correlation_auto_generated_if_missing()
    {
        $message = MessageBuilder::withPayload('some')
            ->build();

        $this->assertNotNull($message->getHeaders()->get(MessageHeaders::MESSAGE_CORRELATION_ID));
    }
}
