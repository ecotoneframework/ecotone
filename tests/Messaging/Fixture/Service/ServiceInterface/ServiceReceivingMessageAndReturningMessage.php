<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceInterface;

use SimplyCodedSoftware\Messaging\Message;

/**
 * Interface ServiceInterface
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ServiceReceivingMessageAndReturningMessage
{
    /**
     * @param Message $message
     *
     * @return Message
     */
    public function execute(Message $message) : Message;
}