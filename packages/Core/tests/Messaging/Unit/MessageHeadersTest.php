<?php

namespace Test\Ecotone\Messaging\Unit;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\MessageHeaderDoesNotExistsException;
use Ecotone\Messaging\MessageHeaders;

/**
 * Class MessageHeaderTest
 * @package Ecotone\Messaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageHeadersTest extends TestCase
{
    public function test_creating_with_generated_headers()
    {
        $headers = MessageHeaders::createEmpty();

        $this->assertEquals(2, $headers->size());
        $this->assertTrue(Uuid::isValid($headers->get(MessageHeaders::MESSAGE_ID)));

        $this->assertFalse($headers->hasMessageId('2db4db21-e3f1-492a-af98-a61468bb03e9'));
        $this->assertTrue($headers->hasMessageId($headers->get(MessageHeaders::MESSAGE_ID)));
    }

    public function test_creating_with_custom_headers()
    {
        $headers = [
            'key' => 'value'
        ];
        $messageHeaders = MessageHeaders::create($headers);

        $this->assertEquals(3, $messageHeaders->size());
    }

    public function test_throwing_exception_when_asking_for_not_existing_header()
    {
        $messageHeaders = MessageHeaders::createEmpty();

        $this->expectException(MessageHeaderDoesNotExistsException::class);

        $messageHeaders->get('some');
    }

    public function test_if_contains_value()
    {
        $messageHeaders = MessageHeaders::createEmpty();

        $this->assertFalse($messageHeaders->containsValue('test'));
    }

    public function test_checking_equality()
    {
        $messageHeaders = MessageHeaders::createEmpty();
        $this->assertFalse(MessageHeaders::createEmpty()->equals($messageHeaders));
        $this->assertTrue($messageHeaders->equals($messageHeaders));
    }

    public function test_creating_with_channels()
    {
        $replyChannel = QueueChannel::create();
        $errorChannel = QueueChannel::create();
        $oldMessageHeaders = MessageHeaders::create([
            MessageHeaders::REPLY_CHANNEL => $replyChannel,
            MessageHeaders::ERROR_CHANNEL => $errorChannel
        ]);

        $this->assertEquals($replyChannel, $oldMessageHeaders->getReplyChannel());
        $this->assertEquals($errorChannel, $oldMessageHeaders->getErrorChannel());
    }

    public function test_creating_with_object_as_value()
    {
        $messageHeader = "some";
        $messageHeaders = MessageHeaders::create([
            $messageHeader => new \stdClass()
        ]);

        $this->assertEquals(
            $messageHeaders->get($messageHeader),
            new \stdClass()
        );
    }

    public function test_retrieving_headers_by_regex()
    {
        $messageHeaders = MessageHeaders::create([
            "ecotone.domain.aggregate" => "name",
            "ecotone.domain.method" => "bla",
            "ecotone.messaging"
        ]);

        $this->assertEquals(
            [
                "ecotone.domain.aggregate" => "name",
                "ecotone.domain.method" => "bla"
            ],
            $messageHeaders->findByRegex("ecotone.domain.*")
        );
    }

    public function test_converting_to_string()
    {
        $messageHeaders = MessageHeaders::create([
            "token" => "123",
            "notConvertableObject" => new \stdClass(),
            "metadata" => [
                "x" => 1
            ]
        ]);

        $this->assertEquals(
            \json_encode([
                "id" => $messageHeaders->get(MessageHeaders::MESSAGE_ID),
                "timestamp" => $messageHeaders->get(MessageHeaders::TIMESTAMP),
                "token" => "123",
                "metadata" => [
                    "x" => 1
                ]
            ]),
            (string)$messageHeaders
        );
    }
}