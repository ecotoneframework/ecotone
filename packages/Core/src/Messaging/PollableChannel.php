<?php
declare(strict_types=1);

namespace Ecotone\Messaging;

/**
 * Interface PollableChannel
 * @package Ecotone\Messaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface PollableChannel extends MessageChannel
{
    /**
     * Receive a message from this channel.
     * Return the next available {@see \Ecotone\Messaging\Message} or {@see null} if interrupted.
     *
     * @return Message|null
     */
    public function receive() : ?Message;

    /**
     * Receive with timeout
     * Tries to receive message till time out passes
     *
     * @param int $timeoutInMilliseconds
     * @return null|Message
     */
    public function receiveWithTimeout(int $timeoutInMilliseconds) : ?Message;
}