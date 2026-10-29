<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\PollableChannel\InMemory;

use Ecotone\Messaging\Consumer\ConsumerPositionTracker;
use Ecotone\Messaging\Endpoint\AcknowledgementCallback;
use Ecotone\Messaging\Endpoint\FinalFailureStrategy;
use Ecotone\Messaging\Support\Assert;
use RuntimeException;

/**
 * Acknowledgement callback for shared channels with position tracking
 *
 * licence Apache-2.0
 */
final class InMemoryStreamingAcknowledgeCallback implements AcknowledgementCallback
{
    public const ECOTONE_IN_MEMORY_SHARED_ACK = 'ecotone.in_memory_shared.ack';

    private int $requeueCount = 0;

    public function __construct(
        private ConsumerPositionTracker $positionTracker,
        private string $endpointId,
        private int $currentPosition,
        private FinalFailureStrategy $failureStrategy = FinalFailureStrategy::RESEND,
        private bool $isAutoAcked = true,
        private InMemoryAcknowledgeStatus $status = InMemoryAcknowledgeStatus::AWAITING,
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

    public function disableAutoAck(): void
    {
        $this->isAutoAcked = false;
    }

    public function getStatus(): InMemoryAcknowledgeStatus
    {
        return $this->status;
    }

    /**
     * Accept the message - move to next position
     */
    public function accept(): void
    {
        Assert::isTrue(
            in_array($this->status, [InMemoryAcknowledgeStatus::AWAITING, InMemoryAcknowledgeStatus::RESENT], true),
            'Message was already acknowledged.'
        );

        $this->status = InMemoryAcknowledgeStatus::ACKED;

        // Move to next position
        $nextPosition = (string)($this->currentPosition + 1);
        $this->positionTracker->savePosition($this->endpointId, $nextPosition);
    }

    /**
     * Reject the message - move to next position (skip this message)
     */
    public function reject(): void
    {
        Assert::isTrue(
            in_array($this->status, [InMemoryAcknowledgeStatus::AWAITING, InMemoryAcknowledgeStatus::RESENT], true),
            'Message was already acknowledged.'
        );

        $this->status = InMemoryAcknowledgeStatus::IGNORED;

        // Move to next position (skip this message)
        $nextPosition = (string)($this->currentPosition + 1);
        $this->positionTracker->savePosition($this->endpointId, $nextPosition);
    }

    /**
     * Resend the message - keep same position (will redeliver)
     */
    public function resend(): void
    {
        Assert::isTrue(
            in_array($this->status, [InMemoryAcknowledgeStatus::AWAITING, InMemoryAcknowledgeStatus::RESENT], true),
            'Message was already acknowledged.'
        );

        $this->status = InMemoryAcknowledgeStatus::RESENT;
        $this->requeueCount++;

        if ($this->requeueCount > 100) {
            throw new RuntimeException('Requeue loop was detected');
        }

        // Do NOT move position - will redeliver same message
    }

    /**
     * Release the message - keep same position (will redeliver)
     */
    public function release(): void
    {
        Assert::isTrue(
            in_array($this->status, [InMemoryAcknowledgeStatus::AWAITING, InMemoryAcknowledgeStatus::RESENT], true),
            'Message was already acknowledged.'
        );

        $this->status = InMemoryAcknowledgeStatus::RESENT;
        $this->requeueCount++;

        if ($this->requeueCount > 100) {
            throw new RuntimeException('Requeue loop was detected');
        }

        // Do NOT move position - will redeliver same message
    }
}
