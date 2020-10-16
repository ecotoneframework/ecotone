<?php

namespace Ecotone\Messaging;

/**
 * Class NullableMessageChannel
 * @package Ecotone\Messaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class NullableMessageChannel implements SubscribableChannel
{
    const CHANNEL_NAME = 'nullChannel';

    /**
     * @return NullableMessageChannel
     */
    public static function create() : self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function send(Message $message): void
    {
    }

    /**
     * @inheritDoc
     */
    public function subscribe(MessageHandler $messageHandler): void
    {
    }

    /**
     * @inheritDoc
     */
    public function unsubscribe(MessageHandler $messageHandler): void
    {
    }


    /**
     * @inheritDoc
     */
    function __toString()
    {
        return "nullable channel";
    }
}