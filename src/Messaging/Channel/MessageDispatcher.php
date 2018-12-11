<?php

namespace SimplyCodedSoftware\Messaging\Channel;

use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageHandler;

/**
 * Interface MessageDispatcher
 * @package SimplyCodedSoftware\Messaging\Dispatcher
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageDispatcher
{
    /**
     * @param MessageHandler $messageHandler
     */
    public function addHandler(MessageHandler $messageHandler) : void;

    /**
     * @param MessageHandler $messageHandler
     */
    public function removeHandler(MessageHandler $messageHandler) : void;

    /**
     * @param Message $message
     */
    public function dispatch(Message $message) : void;

    /**
     * @return int
     */
    public function getHandlerCount() : int;
}