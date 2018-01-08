<?php

namespace Test\SimplyCodedSoftware\Messaging\Store;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\Messaging\MessageHeaders;
use SimplyCodedSoftware\Messaging\Store\SimpleMessageStore;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class SimpleMessageStoreTest
 * @package SimplyCodedSoftware\Messaging\Store
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SimpleMessageStoreTest extends TestCase
{
    public function test_adding_message()
    {
        $message = MessageBuilder::withPayload('some')
                                    ->build();

        $simpleMessageStore = SimpleMessageStore::createEmpty();

        $this->assertNull($simpleMessageStore->getSingleMessage(Uuid::fromString($message->getHeaders()->get(MessageHeaders::MESSAGE_ID))));
        $simpleMessageStore->addSingleMessage($message);

        $this->assertEquals(
            $message,
            $simpleMessageStore->getSingleMessage(Uuid::fromString($message->getHeaders()->get(MessageHeaders::MESSAGE_ID)))
        );
        $this->assertEquals(1, $simpleMessageStore->getSingleMessagesCount());
    }

    public function add_message_to_group()
    {
        $message = MessageBuilder::withPayload('some')
            ->build();

        $simpleMessageStore = SimpleMessageStore::createEmpty();

        $groupId = 'some-group';
        $simpleMessageStore->addMessageToGroup($groupId, $message);

        $this->assertEquals(
            $message,
            $simpleMessageStore->pollMessageFromGroup($groupId)
        );
    }
}