<?php

namespace Fixture\Service\ServiceInterface;

use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Interface ServiceInterface
 * @package Fixture\Service
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