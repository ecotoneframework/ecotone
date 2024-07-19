<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\PollableChannel\InMemory;

use Ecotone\Messaging\Endpoint\AcknowledgementCallback;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Support\Assert;
use RuntimeException;

/**
 * licence Apache-2.0
 */
final class InMemoryAcknowledgeCallback implements AcknowledgementCallback
{
    public function __construct(
        private PollableChannel $queueChannel,
        private Message $message,
        private bool $isAutoAck = true,
        private bool $wasAcked = false
    ) {
    }

    /**
     * @return bool
     */
    public function isAutoAck(): bool
    {
        return $this->isAutoAck;
    }

    /**
     * Disable auto acknowledgment
     */
    public function disableAutoAck(): void
    {
        $this->isAutoAck = false;
    }

    /**
     * Mark the message as accepted
     */
    public function accept(): void
    {
        Assert::isFalse($this->wasAcked, 'Trying to acknowledge message that was already acknowledged');

        $this->wasAcked = true;
    }

    /**
     * Mark the message as rejected
     */
    public function reject(): void
    {
        Assert::isFalse($this->wasAcked, 'Trying to acknowledge message that was already acknowledged');

        $this->wasAcked = true;
    }

    private int $requeueCount = 0;

    /**
     * Reject the message and requeue so that it will be redelivered
     */
    public function requeue(): void
    {
        $this->requeueCount++;

        if ($this->requeueCount > 100) {
            throw new RuntimeException('Requeue loop was detected');
        }

        $this->queueChannel->send($this->message);
    }
}
