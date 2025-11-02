<?php

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\PollableChannel;

/**
 * licence Apache-2.0
 */
class QueueChannel implements PollableChannel, DefinedObject
{
    /**
     * @var Message[] $queue
     */
    private array $queue = [];

    public function __construct(private string $name)
    {
    }

    public static function create(string $name = 'unknown'): self
    {
        return new self($name);
    }

    /**
     * @inheritDoc
     */
    public function send(Message $message): void
    {
        $this->queue[] = $message;
    }

    public function sendToBeginning(Message $message): void
    {
        $this->queue = array_merge([$message], $this->queue);
    }

    /**
     * @inheritDoc
     */
    public function receive(): ?Message
    {
        return array_shift($this->queue);
    }

    /**
     * @inheritDoc
     */
    public function receiveWithTimeout(PollingMetadata $pollingMetadata): ?Message
    {
        return $this->receive();
    }

    public function onConsumerStop(): void
    {
        // No cleanup needed for queue channels
    }

    public function __toString()
    {
        return 'in memory queue: ' . $this->name;
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [$this->name]);
    }
}
