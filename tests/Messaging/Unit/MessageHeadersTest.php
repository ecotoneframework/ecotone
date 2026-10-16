<?php

namespace Test\Ecotone\Messaging\Unit;

use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\InvalidMessageHeaderException;
use Ecotone\Messaging\MessageHeaderDoesNotExistsException;

use function json_encode;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use stdClass;

/**
 * Class MessageHeaderTest
 * @package Ecotone\Messaging
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class MessageHeadersTest extends TestCase
{
    public function test_creating_with_generated_headers()
    {
        $headers = MessageHeaders::create([MessageHeaders::TIMESTAMP => 1700000000]);

        $this->assertEquals(3, $headers->size());
        $this->assertTrue(Uuid::isValid($headers->get(MessageHeaders::MESSAGE_ID)));

        $this->assertFalse($headers->hasMessageId('2db4db21-e3f1-492a-af98-a61468bb03e9'));
        $this->assertTrue($headers->hasMessageId($headers->get(MessageHeaders::MESSAGE_ID)));
    }

    public function test_creating_with_custom_headers()
    {
        $headers = [
            'key' => 'value',
            MessageHeaders::TIMESTAMP => 1700000000,
        ];
        $messageHeaders = MessageHeaders::create($headers);

        $this->assertEquals(4, $messageHeaders->size());
    }

    public function test_creating_with_tiumestamp_as_string()
    {
        $headers = [
            MessageHeaders::TIMESTAMP => '1738512870',
        ];
        $messageHeaders = MessageHeaders::create($headers);

        $this->assertEquals(1738512870, $messageHeaders->getTimestamp());
    }

    public function test_non_numeric_timestamp_is_refused_as_missing()
    {
        $this->expectException(InvalidMessageHeaderException::class);
        $this->expectExceptionMessage('Message headers have no timestamp.');

        MessageHeaders::create([MessageHeaders::TIMESTAMP => 'test']);
    }

    public function test_throwing_exception_when_asking_for_not_existing_header()
    {
        $messageHeaders = MessageHeaders::create([MessageHeaders::TIMESTAMP => 1700000000]);

        $this->expectException(MessageHeaderDoesNotExistsException::class);

        $messageHeaders->get('some');
    }

    public function test_if_contains_value()
    {
        $messageHeaders = MessageHeaders::create([MessageHeaders::TIMESTAMP => 1700000000]);

        $this->assertFalse($messageHeaders->containsValue('test'));
    }

    public function test_checking_equality()
    {
        $messageHeaders = MessageHeaders::create([MessageHeaders::TIMESTAMP => 1700000000]);
        $this->assertFalse(MessageHeaders::create([MessageHeaders::TIMESTAMP => 1700000000])->equals($messageHeaders));
        $this->assertTrue($messageHeaders->equals($messageHeaders));
    }

    public function test_creating_with_channels()
    {
        $replyChannel = QueueChannel::create();
        $errorChannel = QueueChannel::create();
        $oldMessageHeaders = MessageHeaders::create([
            MessageHeaders::TIMESTAMP => 1700000000,
            MessageHeaders::REPLY_CHANNEL => $replyChannel,
            MessageHeaders::ERROR_CHANNEL => $errorChannel,
        ]);

        $this->assertEquals($replyChannel, $oldMessageHeaders->getReplyChannel());
        $this->assertEquals($errorChannel, $oldMessageHeaders->getErrorChannel());
    }

    public function test_creating_with_object_as_value()
    {
        $messageHeader = 'some';
        $messageHeaders = MessageHeaders::create([
            MessageHeaders::TIMESTAMP => 1700000000,
            $messageHeader => new stdClass(),
        ]);

        $this->assertEquals(
            $messageHeaders->get($messageHeader),
            new stdClass()
        );
    }

    public function test_retrieving_headers_by_regex()
    {
        $messageHeaders = MessageHeaders::create([
            MessageHeaders::TIMESTAMP => 1700000000,
            'ecotone.domain.aggregate' => 'name',
            'ecotone.domain.method' => 'bla',
            'ecotone.messaging' => 'test',
        ]);

        $this->assertEquals(
            [
                'ecotone.domain.aggregate' => 'name',
                'ecotone.domain.method' => 'bla',
            ],
            $messageHeaders->findByRegex('ecotone.domain.*')
        );
    }

    public function test_converting_to_string()
    {
        $messageHeaders = MessageHeaders::create([
            'token' => '123',
            'notConvertableObject' => new stdClass(),
            'metadata' => [
                'x' => 1,
            ],
            MessageHeaders::TIMESTAMP => 1700000000,
        ]);

        $this->assertEquals(
            json_encode([
                'token' => '123',
                'metadata' => [
                    'x' => 1,
                ],
                'timestamp' => 1700000000,
                'id' => $messageHeaders->get(MessageHeaders::MESSAGE_ID),
                MessageHeaders::MESSAGE_CORRELATION_ID => $messageHeaders->getCorrelationId(),
            ]),
            (string)$messageHeaders
        );
    }
}
