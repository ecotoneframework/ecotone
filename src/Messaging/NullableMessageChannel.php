<?php

namespace Ecotone\Messaging;

/**
 * Class NullableMessageChannel
 * @package Ecotone\Messaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class NullableMessageChannel implements SubscribableChannel, PollableChannel
{
    public const CHANNEL_NAME = 'nullChannel';

    /**
     * @return NullableMessageChannel
     */
    public static function create(): self
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

    public function receive(): ?Message
    {
        return null;
    }

    public function receiveWithTimeout(int $timeoutInMilliseconds): ?Message
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return 'null';
    }
}
