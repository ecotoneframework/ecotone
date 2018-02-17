<?php

namespace Fixture\Handler\Splitter;

use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class ServiceSplittingArrayPayload
 * @package Fixture\Handler\Splitter
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