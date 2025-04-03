<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Channel;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Message;
use InvalidArgumentException;

/**
 * Test implementation of QueueChannel for PHPUnit 10 compatibility
 */
class TestQueueChannel extends QueueChannel
{
    private bool $throwException = false;
    private ?Message $messageToReturn = null;
    private ?Message $lastSentMessage = null;

    public function __construct(string $name = 'unknown', bool $throwException = false, ?Message $messageToReturn = null)
    {
        parent::__construct($name);
        $this->throwException = $throwException;
        $this->messageToReturn = $messageToReturn;
    }

    public static function create(string $name = 'unknown'): self
    {
        return new self($name);
    }

    public static function createWithException(bool $throwException = true, ?Message $messageToReturn = null): self
    {
        return new self('unknown', $throwException, $messageToReturn);
    }

    public function send(Message $message): void
    {
        $this->lastSentMessage = $message;

        if ($this->throwException) {
            throw new InvalidArgumentException('Test exception');
        }

        parent::send($message);
    }

    public function receive(): ?Message
    {
        if ($this->throwException) {
            throw new InvalidArgumentException('Test exception');
        }

        if ($this->messageToReturn) {
            return $this->messageToReturn;
        }

        return parent::receive();
    }

    public function getLastSentMessage(): ?Message
    {
        return $this->lastSentMessage;
    }
}
