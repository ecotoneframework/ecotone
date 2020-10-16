<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\PollableChannel;

class ExceptionalQueueChannel implements PollableChannel
{
    private int $exceptionCount = 0;

    private function __construct()
    {
    }

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
    public function receive(): ?Message
    {
        $this->exceptionCount++;
        throw new \RuntimeException();
    }

    /**
     * @inheritDoc
     */
    public function receiveWithTimeout(int $timeoutInMilliseconds): ?Message
    {
        return $this->receive();
    }

    public function getExceptionCount(): int
    {
        return $this->exceptionCount;
    }
}