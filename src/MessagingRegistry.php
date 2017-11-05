<?php

namespace Messaging;

/**
 * Interface ServiceContainer
 * @package Messaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessagingRegistry
{
    /**
     * @param string|MessageChannel $messageChannel if object is passed, it should return same object, otherwise it should retrieve it from registry
     * @return MessageChannel
     * @throws MessagingServiceIsNotAvailable if not found message channel
     */
    public function getMessageChannel($messageChannel) : MessageChannel;
}