<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\Manager;

use Ecotone\Messaging\Config\Container\DefinedObject;

/**
 * Interface for managing message channel initialization.
 * Implementations handle specific broker types (AMQP, SQS, etc.)
 *
 * licence Apache-2.0
 */
interface ChannelManager extends DefinedObject
{
    /**
     * Returns the channel name
     */
    public function getChannelName(): string;

    /**
     * Returns the channel type (e.g., 'amqp', 'sqs')
     */
    public function getChannelType(): string;

    /**
     * Initialize the channel (create queues/exchanges/topics)
     */
    public function initialize(): void;

    /**
     * Delete the channel resources
     */
    public function delete(): void;

    /**
     * Check if the channel is initialized
     */
    public function isInitialized(): bool;

    /**
     * Returns whether this channel should be automatically initialized at runtime
     */
    public function shouldBeInitializedAutomatically(): bool;
}
