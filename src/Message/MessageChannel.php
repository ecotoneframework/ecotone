<?php

namespace Messaging\Message;

use Messaging\Exception\Message\MessageSendException;

/**
 * Interface MessageChannel
 * @package Messaging\Message
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageChannel
{
    /**
     * Send message to this channel
     *
     * @param Message $message
     * @return void
     * @throws MessageSendException
     */
    public function send(Message $message) : void;
}