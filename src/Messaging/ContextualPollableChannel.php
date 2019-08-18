<?php
declare(strict_types=1);


namespace Ecotone\Messaging;

/**
 * Interface ContextualPollableChannel
 * @package Ecotone\Messaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ContextualPollableChannel extends PollableChannel
{
    /**
     * Receive a message from this channel.
     * Return the next available {@see \Ecotone\Messaging\Message} or {@see null} if interrupted.
     *
     * @param string $endpointId
     * @return Message|null
     */
    public function receiveWithEndpointId(string $endpointId) : ?Message;

    /**
     * Receive with timeout
     * Tries to receive message till time out passes
     *
     * @param string $endpointId
     * @param int $timeoutInMilliseconds
     * @return null|Message
     */
    public function receiveWithEndpointIdAndTimeout(string $endpointId, int $timeoutInMilliseconds) : ?Message;
}