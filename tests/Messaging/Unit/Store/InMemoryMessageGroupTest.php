<?php

namespace Test\Ecotone\Messaging\Unit\Store;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ecotone\Messaging\Store\InMemoryMessageGroup;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class InMemoryMessageGroupTest
 * @package Ecotone\Messaging\Store
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InMemoryMessageGroupTest extends TestCase
{
    public function test_adding_message()
    {
        $message = MessageBuilder::withPayload('some')
                        ->build();

        $messageGroup = InMemoryMessageGroup::createEmpty();

        $this->assertTrue($messageGroup->isEmpty());
        $this->assertTrue($messageGroup->canBeAdded($message));

        $messageGroup->add($message);

        $this->assertFalse($messageGroup->isEmpty());
        $this->assertEquals([$message], $messageGroup->messages());
    }

    public function test_removing_message()
    {
        $message = MessageBuilder::withPayload('some')
            ->build();
        $groupId = Uuid::fromString('3d6cdcc8-8b72-4ef8-a652-566899d37d39')->toString();

        $messageGroup = InMemoryMessageGroup::createEmptyWithId($groupId);

        $messageGroup->add($message);
        $this->assertEquals(1, $messageGroup->size());

        $messageGroup->remove($message);
        $this->assertEquals(0, $messageGroup->size());
    }

    public function test_clearing_group()
    {
        $message = MessageBuilder::withPayload('some')
            ->build();
        $messageGroup = InMemoryMessageGroup::createEmpty();

        $messageGroup->add($message);
        $messageGroup->clear();

        $this->assertTrue($messageGroup->isEmpty());
    }

    public function test_omitting_message_when_duplicate()
    {
        $message = MessageBuilder::withPayload('some')
            ->build();
        $messageGroup = InMemoryMessageGroup::createEmpty();

        $messageGroup->add($message);
        $messageGroup->add($message);

        $this->assertEquals(1, $messageGroup->size());
    }

    public function test_adding_multiple_messages()
    {
        $messageGroup = InMemoryMessageGroup::createEmpty();

        $messageGroup->add(
            MessageBuilder::withPayload('some')
                ->build()
        );
        $messageGroup->add(
            MessageBuilder::withPayload('some2')
                ->build()
        );

        $this->assertEquals(2, $messageGroup->size());
    }
}