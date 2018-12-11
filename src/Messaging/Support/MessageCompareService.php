<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Support;

use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageHeaders;

/**
 * Class MessageCompareService
 * @package SimplyCodedSoftware\Messaging\Support
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageCompareService
{
    public static function areSameMessagesIgnoringIdAndTimestamp(Message $message, Message $toCompareWith) : bool
    {
        if ($message->getPayload() != $toCompareWith->getPayload()) {
            return false;
        }

        $messageHeaders = $message->getHeaders()->headers();
        $messagesHeadersToCompare = $toCompareWith->getHeaders()->headers();

        unset($messageHeaders[MessageHeaders::MESSAGE_ID]);
        unset($messageHeaders[MessageHeaders::TIMESTAMP]);
        unset($messagesHeadersToCompare[MessageHeaders::MESSAGE_ID]);
        unset($messagesHeadersToCompare[MessageHeaders::TIMESTAMP]);

        if ($messageHeaders != $messagesHeadersToCompare) {
            return false;
        }

        return true;
    }
}