<?php

namespace Messaging\Config;

use Messaging\Endpoint\ConsumerLifecycle;
use Messaging\MessageHandler;
use Messaging\MessagingRegistry;

/**
 * Interface Configuration
 * @package Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ConfigurationRegistry extends MessagingRegistry
{
    /**
     * @param string $handlerName
     * @return MessageHandler
     */
    public function getHandler(string $handlerName) : MessageHandler;

    /**
     * @param ConsumerLifecycle $consumerLifecycle
     */
    public function register(ConsumerLifecycle $consumerLifecycle) : void;
}