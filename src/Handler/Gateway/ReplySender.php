<?php

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;

use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Interface Poller - Receive reply from request channel and forward it internally
 * @package SimplyCodedSoftware\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ReplySender
{
    /**
     * @param InterfaceToCall $interfaceToCall
     * @param MessageBuilder $messageBuilder
     * @return MessageBuilder
     */
    public function prepareFor(InterfaceToCall $interfaceToCall, MessageBuilder $messageBuilder) : MessageBuilder;

    /**
     * Receives reply after sending message to request channel and forward it internally
     */
    public function receiveReply() : ?Message;

    /**
     * @return bool
     */
    public function hasReply() : bool;
}