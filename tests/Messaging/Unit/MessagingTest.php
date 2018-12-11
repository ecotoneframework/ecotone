<?php

namespace Test\SimplyCodedSoftware\Messaging\Unit;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageHeaders;
use SimplyCodedSoftware\Messaging\Support\MessageCompareService;

/**
 * Class MessagingTest
 * @package SimplyCodedSoftware\Messaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class MessagingTest extends TestCase
{
    const FIXTURE_DIR = __DIR__ . '/../Fixture';

    const ROOT_DIR = self::FIXTURE_DIR . '/../../..';

    public function assertMessages(Message $message, Message $toCompareWith) : void
    {
        if (!MessageCompareService::areSameMessagesIgnoringIdAndTimestamp($message, $toCompareWith)) {
            $this->assertEquals($message, $toCompareWith);
        }else {
            $this->assertTrue(true);
        }
    }

    public function assertMultipleMessages(array $messages, array $messagesToCompareWith) : void
    {
        $messagesAmount = count($messages);
        Assert::assertCount($messagesAmount, $messagesToCompareWith, "Amount of messages is different");

        for ($i = 0; $i < $messagesAmount; $i++) {
            $this->assertMessages($messages[$i], $messagesToCompareWith[$i]);
        }
    }
}