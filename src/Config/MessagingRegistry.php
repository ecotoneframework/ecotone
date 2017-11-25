<?php

namespace Messaging\Config;

use Messaging\Endpoint\MessageHandlerRegistration;
use Messaging\MessageChannel;
use Messaging\MessageHandler;

/**
 * Interface MessagingRegistry
 * @package Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessagingRegistry
{
    /**
     * @return array|MessageChannel
     */
    public function getAllChannels() : array;

    /**
     * @return array|MessageHandler[]
     */
    public function getAllMessageHandlers() : array;

    /**
     * @return array|MessageHandlerRegistration[]
     */
    public function messageHandlerToChannelConnection() : array;
}