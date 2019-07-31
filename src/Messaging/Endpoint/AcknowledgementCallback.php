<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint;

/**
 * Allows to ack message
 *
 * Interface Acknowledge
 * @package Ecotone\Messaging\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface AcknowledgementCallback
{
    /**
     * @return bool
     */
    public function isAutoAck() : bool;

    /**
     * Disable auto acknowledgment
     */
    public function disableAutoAck() : void;

    /**
     * Mark the message as accepted
     */
    public function accept() : void;

    /**
     * Mark the message as rejected
     */
    public function reject() : void;

    /**
     * Reject the message and requeue so that it will be redelivered
     */
    public function requeue() : void;
}