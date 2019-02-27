<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Transaction;

use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\Transaction\Transaction;
use SimplyCodedSoftware\Messaging\Transaction\TransactionException;

/**
 * Class FakeTransaction
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Transaction
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class FakeTransaction implements Transaction
{
    private const ACTIVE = 1;
    private const COMMITTED = 2;
    private const MARKED_ROLLBACK = 3;
    private const ROLLED_BACK = 4;

    /**
     * @var int
     */
    private $status;

    /**
     * FakeTransaction constructor.
     *
     * @param int $status
     */
    private function __construct(int $status)
    {
        $this->status = $status;
    }

    /**
     * @return FakeTransaction
     */
    public static function begin() : self
    {
        return new self(self::ACTIVE);
    }

    /**
     * @inheritDoc
     */
    public function commit(): void
    {
        if (!$this->isActive()) {
            throw TransactionException::createWith("Can't commit not active transaction");
        }

        $this->status = self::COMMITTED;
    }

    /**
     * @inheritDoc
     */
    public function rollback(): void
    {
        if (!$this->isActive()) {
            throw TransactionException::createWith("Can't rollback not active transaction");
        }

        $this->status = self::ROLLED_BACK;
    }

    /**
     * @inheritDoc
     */
    public function setRollbackOnly(): void
    {
        $this->status = self::MARKED_ROLLBACK;
    }

    /**
     * @inheritDoc
     */
    public function isActive(): bool
    {
        return $this->status === self::ACTIVE;
    }

    /**
     * @inheritDoc
     */
    public function isCommitted(): bool
    {
        return $this->status === self::COMMITTED;
    }

    /**
     * @inheritDoc
     */
    public function isRolledBack(): bool
    {
        return $this->status === self::ROLLED_BACK;
    }

    /**
     * @inheritDoc
     */
    public function isMarkedToRollback(): bool
    {
        return $this->status === self::MARKED_ROLLBACK;
    }

}