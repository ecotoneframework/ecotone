<?php

namespace Messaging\Handler;

use Messaging\Message;

/**
 * Interface Poller
 * @package Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ReplySender
{
    /**
     * Receives reply after sending message to request channel and sends to next channel
     */
    public function receiveReply() : void;
}