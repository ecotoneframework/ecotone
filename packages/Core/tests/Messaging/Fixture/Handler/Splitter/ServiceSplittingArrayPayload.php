<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Splitter;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class ServiceSplittingArrayPayload
 * @package Test\Ecotone\Messaging\Fixture\Handler\Splitter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceSplittingArrayPayload
{
    /**
     * @param Message $message
     * @return array
     */
    public function splitToPayload(Message $message) : array
    {
        return $message->getPayload();
    }

    /**
     * @param Message $message
     * @return array
     */
    public function splitToMessages(Message $message) : array
    {
        $splittedMessages = [];

        foreach ($message->getPayload() as $value) {
            $splittedMessages[] = MessageBuilder::fromMessage($message)
                ->setPayload($value)
                ->build();
        }

        return $splittedMessages;
    }
}