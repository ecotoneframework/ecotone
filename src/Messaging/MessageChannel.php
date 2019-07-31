<?php
declare(strict_types=1);

namespace Ecotone\Messaging;

/**
 * Interface MessageChannel
 * @package Ecotone\Messaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageChannel
{
    /**
     * Send message to this channel
     *
     * @param Message $message
     * @return void
     */
    public function send(Message $message) : void;
}