<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Message;

/**
 * licence Apache-2.0
 */
final class InMemoryMessageChannelHolder implements DefinedObject
{
    private array $messages = [];

    /**
     * Send message to this channel
     */
    public function send(int $position, Message $message): void
    {
        $this->messages[$position] = $message;
    }

    /**
     * Receive with timeout using polling metadata
     * Tries to receive message till time out passes
     *
     * @param PollingMetadata $pollingMetadata Contains timeout and execution constraints
     */
    public function receiveFor(int $position): ?Message
    {
        return $this->messages[$position] ?? null;
    }

    /**
     * Called when the consumer is about to stop
     * This allows the poller to perform cleanup operations like committing pending messages
     */
    public function onConsumerStop(): void
    {

    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class);
    }
}
