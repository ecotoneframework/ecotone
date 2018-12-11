<?php

namespace SimplyCodedSoftware\Messaging;

/**
 * Interface PollableChannel
 * @package SimplyCodedSoftware\Messaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface PollableChannel extends MessageChannel
{
    /**
     * Receive a message from this channel.
     * Return the next available {@see \SimplyCodedSoftware\Messaging\Message} or {@see null} if interrupted.
     *
     * @return Message|null
     */
    public function receive() : ?Message;
}