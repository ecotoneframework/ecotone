<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Channel;

use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;

/**
 * Interface MessageDispatcher
 * @package SimplyCodedSoftware\IntegrationMessaging\Dispatcher
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