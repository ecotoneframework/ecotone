<?php

namespace SimplyCodedSoftware\IntegrationMessaging;

/**
 * Interface PollableChannel
 * @package SimplyCodedSoftware\IntegrationMessaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface PollableChannel extends MessageChannel
{
    /**
     * Receive a message from this channel.
     * Return the next available {@see \SimplyCodedSoftware\IntegrationMessaging\Message} or {@see null} if interrupted.
     *
     * @return Message|null
     */
    public function receive() : ?Message;
}