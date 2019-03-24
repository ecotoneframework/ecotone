<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Amqp;

use SimplyCodedSoftware\Messaging\Transaction\Transaction;
use SimplyCodedSoftware\Messaging\Transaction\TransactionException;

/**
 * Class AcknowledgeTransaction
 * @package SimplyCodedSoftware\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AcknowledgeTransaction implements Transaction
{
    /**
     * @var AmqpAcknowledgementCallback
     */
    private $amqpAcknowledgementCallback;

    /**
     * AcknowledgeTransaction constructor.
     * @param AmqpAcknowledgementCallback $amqpAcknowledgementCallback
     */
    public function __construct(AmqpAcknowledgementCallback $amqpAcknowledgementCallback)
    {
        $this->amqpAcknowledgementCallback = $amqpAcknowledgementCallback;
    }

    /**
     * @inheritDoc
     */
    public function commit(): void
    {
        $this->amqpAcknowledgementCallback->accept();
    }

    /**
     * @inheritDoc
     */
    public function rollback(): void
    {
        $this->amqpAcknowledgementCallback->requeue();
    }

    /**
     * @inheritDoc
     */
    public function setRollbackOnly(): void
    {
        return;
    }
}