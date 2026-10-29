<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\PollableChannel\InMemory;

use Ecotone\Messaging\Channel\DelayableQueueChannel;
use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Endpoint\AcknowledgementCallback;
use Ecotone\Messaging\Endpoint\FinalFailureStrategy;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\Assert;
use RuntimeException;

/**
 * licence Apache-2.0
 */
final class InMemoryAcknowledgeCallback implements AcknowledgementCallback
{
    public function __construct(
        private QueueChannel|DelayableQueueChannel $queueChannel,
        private Message                   $message,
        private FinalFailureStrategy      $failureStrategy = FinalFailureStrategy::RESEND,
        private bool                      $isAutoAcked = true,
        private InMemoryAcknowledgeStatus $status = InMemoryAcknowledgeStatus::AWAITING
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getFailureStrategy(): FinalFailureStrategy
    {
        return $this->failureStrategy;
    }

    /**
     * @inheritDoc
     */
    public function isAutoAcked(): bool
    {
        return $this->isAutoAcked;
    }

    public function getStatus(): InMemoryAcknowledgeStatus
    {
        return $this->status;
    }

    /**
     * Mark the message as accepted
     */
    public function accept(): void
    {
        Assert::isTrue(in_array($this->status, [InMemoryAcknowledgeStatus::AWAITING, InMemoryAcknowledgeStatus::RESENT], true), 'Message was already acknowledged.');
        $this->status = InMemoryAcknowledgeStatus::ACKED;
    }

    /**
     * Mark the message as rejected
     */
    public function reject(): void
    {
        Assert::isTrue(in_array($this->status, [InMemoryAcknowledgeStatus::AWAITING, InMemoryAcknowledgeStatus::RESENT], true), 'Message was already acknowledged.');
        $this->status = InMemoryAcknowledgeStatus::IGNORED;
    }

    private int $requeueCount = 0;

    /**
     * Reject the message and requeue so that it will be redelivered
     */
    public function resend(): void
    {
        Assert::isTrue(in_array($this->status, [InMemoryAcknowledgeStatus::AWAITING, InMemoryAcknowledgeStatus::RESENT], true), 'Message was already acknowledged.');

        $this->status = InMemoryAcknowledgeStatus::RESENT;
        $this->requeueCount++;

        if ($this->requeueCount > 100) {
            throw new RuntimeException('Requeue loop was detected');
        }

        $this->queueChannel->send($this->message);
    }

    /**
     * Release the message back to the end of the channel
     */
    public function release(): void
    {
        Assert::isTrue(in_array($this->status, [InMemoryAcknowledgeStatus::AWAITING, InMemoryAcknowledgeStatus::RESENT], true), 'Message was already acknowledged.');

        $this->status = InMemoryAcknowledgeStatus::RESENT;
        $this->requeueCount++;

        if ($this->requeueCount > 100) {
            throw new RuntimeException('Requeue loop was detected');
        }

        $this->queueChannel->sendToBeginning($this->message);
    }

    /**
     * Requeue the message using the original mechanism
     */
    public function requeue(): void
    {
        Assert::isTrue(in_array($this->status, [InMemoryAcknowledgeStatus::AWAITING, InMemoryAcknowledgeStatus::RESENT], true), 'Message was already acknowledged.');

        $this->status = InMemoryAcknowledgeStatus::RESENT;
        $this->requeueCount++;

        if ($this->requeueCount > 100) {
            throw new RuntimeException('Requeue loop was detected');
        }

        $this->queueChannel->send($this->message);
    }
}
