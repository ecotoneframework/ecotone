<?php

namespace Messaging;

use Messaging\Exception\MessageDeliveryException;

/**
 * Interface MessageChannel
 * @package Messaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageChannel
{
    /**
     * Send message to this channel
     *
     * @param Message $message
     * @return void
     * @throws MessageDeliveryException
     */
    public function send(Message $message) : void;
}