<?php

namespace Ecotone\Messaging;

/**
 * Interface SubscribableChannel
 * @package Ecotone\Messaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface SubscribableChannel extends MessageChannel
{
    /**
     *  A {@link MessagingConfiguration} that maintains a registry of subscribers and invokes
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