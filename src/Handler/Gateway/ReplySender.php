<?php

namespace Messaging\Handler\Gateway;

use Messaging\Message;
use Messaging\Support\MessageBuilder;

/**
 * Interface Poller - Receive reply from request channel and forward it internally
 * @package Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ReplySender
{
    /**
     * @param MessageBuilder $messageBuilder
     * @return MessageBuilder
     */
    public function addErrorChannel(MessageBuilder $messageBuilder) : MessageBuilder;

    /**
     * Receives reply after sending message to request channel and forward it internally
     */
    public function receiveReply() : ?Message;

    /**
     * @return bool
     */
    public function hasReply() : bool;
}