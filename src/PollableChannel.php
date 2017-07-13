<?php

namespace Messaging;

/**
 * Interface PollableChannel
 * @package Messaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface PollableChannel extends MessageChannel
{
    /**
     * Receive a message from this channel.
     * Return the next available {@see \Messaging\Message} or {@see null} if interrupted.
     *
     * @return Message|null
     */
    public function receive() : ?Message;
}