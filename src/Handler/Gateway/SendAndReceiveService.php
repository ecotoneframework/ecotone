<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway;

use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageChannel;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Interface Poller - Receive reply from request channel and forward it internally
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler
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