<?php

namespace Messaging\Handler\Gateway;

use Messaging\Message;

/**
 * Interface Poller - Receive reply from request channel and forward it internally
 * @package Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ReplySender
{
    /**
     * Receives reply after sending message to request channel and forward it internally
     */
    public function receiveReply() : ?Message;

    /**
     * @return bool
     */
    public function hasReply() : bool;
}