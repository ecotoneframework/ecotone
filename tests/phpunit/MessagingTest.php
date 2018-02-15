<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging;

use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageHeaders;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageCompareService;

/**
 * Class MessagingTest
 * @package SimplyCodedSoftware\IntegrationMessaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class MessagingTest extends TestCase
{
    const FIXTURE_DIR = __DIR__ . '/../Fixture';

    public function assertMessages(Message $message, Message $toCompareWith) : void
    {
        $this->assertTrue(MessageCompareService::areSameMessagesIgnoringIdAndTimestamp($message, $toCompareWith), "Messages are not the same");
    }
}