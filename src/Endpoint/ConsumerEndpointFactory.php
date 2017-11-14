<?php

namespace Messaging\Endpoint;

use Messaging\MessageChannel;
use Messaging\MessageHandler;
use Messaging\SubscribableChannel;

/**
 * Class ConsumerEndpointFactory
 * @package Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ConsumerEndpointFactory
{
    /**
     * @var MessageHandler
     */
    private $messageHandler;
    /**
     * @var MessageChannel
     */
    private $messageChannel;

    /**
     * @param MessageHandler $messageHandler
     */
    public function setMessageHandler(MessageHandler $messageHandler) : void
    {
        $this->messageHandler = $messageHandler;
    }

    /**
     * @param MessageChannel $messageChannel
     */
    public function setMessageChannel(MessageChannel $messageChannel) : void
    {
        $this->messageChannel = $messageChannel;
    }

    public function create() : ConsumerLifecycle
    {
        if ($this->messageChannel instanceof SubscribableChannel) {
            return new EventDrivenConsumer($this->messageChannel, $this->messageHandler);
        }
    }
}