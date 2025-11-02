<?php

namespace Ecotone\Messaging;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Endpoint\PollingMetadata;

/**
 * Class NullableMessageChannel
 * @package Ecotone\Messaging
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
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

    public function receiveWithTimeout(PollingMetadata $pollingMetadata): ?Message
    {
        return null;
    }

    public function onConsumerStop(): void
    {
        // No cleanup needed for null channels
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
