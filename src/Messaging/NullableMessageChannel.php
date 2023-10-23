<?php

namespace Ecotone\Messaging;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;

/**
 * Class NullableMessageChannel
 * @package Ecotone\Messaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class NullableMessageChannel implements SubscribableChannel, PollableChannel, DefinedObject
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

    public function getDefinition(): Definition
    {
        return new Definition(self::class);
    }
}
