<?php

namespace Messaging;

/**
 * Interface SubscribableChannel
 * @package Messaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface SubscribableChannel extends MessageChannel
{
    /**
     *  A {@link MessageChannel} that maintains a registry of subscribers and invokes
     * them to handle {@link Message} sent through this channel.
     *
     * @param MessageHandler $messageHandler
     * @return void
     */
    public function subscribe(MessageHandler $messageHandler) : void;

    /**
     * Un-register a {@link MessageHandler}.
     *
     * @param MessageHandler $messageHandler
     * @return void
     */
    public function unsubscribe(MessageHandler $messageHandler) : void;
}