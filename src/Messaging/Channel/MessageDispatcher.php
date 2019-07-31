<?php

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHandler;

/**
 * Interface MessageDispatcher
 * @package Ecotone\Messaging\Dispatcher
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