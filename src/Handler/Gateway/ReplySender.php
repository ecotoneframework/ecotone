<?php

namespace Messaging\Handler\Gateway;

/**
 * Interface Poller - Receive reply after sending message to request channel and forward it internally
 * @package Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ReplySender
{
    /**
     * Receives reply after sending message to request channel and forward it internally
     */
    public function receiveAndForwardReply() : void;
}