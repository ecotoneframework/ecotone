<?php

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\PollableChannel;

class QueueChannel implements PollableChannel
{
    /**
     * @param Message[] $queue
     */
    private function __construct(private string $name, private array $queue)
    {
    }

    public static function create(string $name = 'unknown'): self
    {
        return new self($name, []);
    }

    /**
     * @inheritDoc
     */
    public function send(Message $message): void
    {
        $this->queue[] = $message;
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
    public function receiveWithTimeout(int $timeoutInMilliseconds): ?Message
    {
        return $this->receive();
    }

    public function __toString()
    {
        return 'in memory queue: ' . $this->name;
    }
}
