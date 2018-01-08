<?php

namespace Test\SimplyCodedSoftware\Messaging;

use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageHeaders;

/**
 * Class MessagingTest
 * @package SimplyCodedSoftware\Messaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class MessagingTest extends TestCase
{
    public function assertMessages(Message $message, Message $toCompareWith) : void
    {
        $this->assertEquals($message->getPayload(), $toCompareWith->getPayload(), "Message payload is different");

        $messageHeaders = $message->getHeaders()->headers();
        $messagesHeadersToCompare = $toCompareWith->getHeaders()->headers();

        unset($messageHeaders[MessageHeaders::MESSAGE_ID]);
        unset($messageHeaders[MessageHeaders::TIMESTAMP]);
        unset($messagesHeadersToCompare[MessageHeaders::MESSAGE_ID]);
        unset($messagesHeadersToCompare[MessageHeaders::TIMESTAMP]);

        $this->assertEquals($messageHeaders, $messagesHeadersToCompare, "Message headers are different");
    }
}