<?php

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;

use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Interface Poller - Receive reply from request channel and forward it internally
 * @package SimplyCodedSoftware\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface SendAndReceiveService
{
    /**
     * @param Message $message
     * @return void
     */
    public function send(Message $message) : void;

    /**
     * @param MessageBuilder $messageBuilder
     * @param InterfaceToCall $interfaceToCall
     * @return MessageBuilder
     */
    public function prepareForSend(MessageBuilder $messageBuilder, InterfaceToCall $interfaceToCall) : MessageBuilder;

    /**
     * Receives reply and forward it internally
     */
    public function receiveReply() : ?Message;
}