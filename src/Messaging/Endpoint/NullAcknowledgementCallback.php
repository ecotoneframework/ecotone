<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint;

use Ecotone\Messaging\Support\Assert;

/**
 * Class NullAcknowledgementCallback
 * @package Ecotone\Messaging\Endpoint
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class NullAcknowledgementCallback implements AcknowledgementCallback
{
    private const AWAITING = 0;
    private const ACKED = 1;
    private const REJECT = 2;
    private const REQUEUED = 3;

    private int $status = self::AWAITING;

    private function __construct(private FinalFailureStrategy $failureStrategy, private bool $isAutoAcked = true)
    {
    }

    /**
     * @return NullAcknowledgementCallback
     */
    public static function create(FinalFailureStrategy $failureStrategy = FinalFailureStrategy::RESEND): self
    {
        return new self($failureStrategy);
    }

    /**
     * @return NullAcknowledgementCallback
     */
    public static function createWithAutoAck(FinalFailureStrategy $failureStrategy = FinalFailureStrategy::RESEND): self
    {
        return new self($failureStrategy, true);
    }

    /**
     * @return NullAcknowledgementCallback
     */
    public static function createWithManualAck(FinalFailureStrategy $failureStrategy = FinalFailureStrategy::RESEND): self
    {
        return new self($failureStrategy, false);
    }

    public function isAcked(): bool
    {
        return $this->status === self::ACKED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::REJECT;
    }

    public function isRequeued(): bool
    {
        return $this->status === self::REQUEUED;
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

    /**
     * @inheritDoc
     */
    public function accept(): void
    {
        Assert::isTrue($this->isAwaiting(), 'Acknowledge was already sent');
        $this->status = self::ACKED;
    }

    /**
     * @inheritDoc
     */
    public function reject(): void
    {
        Assert::isTrue($this->isAwaiting(), 'Acknowledge was already sent');
        $this->status = self::REJECT;
    }

    /**
     * @inheritDoc
     */
    public function resend(): void
    {
        Assert::isTrue($this->isAwaiting(), 'Acknowledge was already sent');
        $this->status = self::REQUEUED;
    }

    /**
     * @inheritDoc
     */
    public function release(): void
    {
        Assert::isTrue($this->isAwaiting(), 'Acknowledge was already sent');
        $this->status = self::REQUEUED;
    }

    /**
     * Requeue the message using the original mechanism
     */
    public function requeue(): void
    {
        Assert::isTrue($this->isAwaiting(), 'Acknowledge was already sent');
        $this->status = self::REQUEUED;
    }

    private function isAwaiting(): bool
    {
        return $this->status === self::AWAITING;
    }
}
