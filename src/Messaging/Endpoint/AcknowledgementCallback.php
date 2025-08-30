<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint;

/**
 * Allows to ack message
 *
 * Interface Acknowledge
 * @package Ecotone\Messaging\Amqp
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface AcknowledgementCallback
{
    /**
     * Get the failure strategy for this acknowledgement callback
     */
    public function getFailureStrategy(): FinalFailureStrategy;

    /**
     * Check if this acknowledgement callback is in auto-ack mode
     */
    public function isAutoAcked(): bool;

    /**
     * Mark the message as accepted
     */
    public function accept(): void;

    /**
     * Mark the message as rejected
     */
    public function reject(): void;

    /**
     * Resends the message back to the original channel
     */
    public function resend(): void;

    /**
     * Releases the message back to the end of the original channel
     */
    public function release(): void;
}
