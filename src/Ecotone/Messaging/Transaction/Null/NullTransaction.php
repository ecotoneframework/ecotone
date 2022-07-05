<?php

namespace Ecotone\Messaging\Transaction\Null;

use Ecotone\Messaging\Transaction\Transaction;
use Ecotone\Messaging\Transaction\TransactionException;

/**
 * Class NullTransaction
 * @package Ecotone\Messaging\Transaction\Null
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class NullTransaction implements Transaction
{
    const COMMITTED = "committed";
    const ROLLED_BACK = "rolledBack";
    private ?string $status;
    private bool $isRollbackOnly = false;

    private function __construct()
    {
    }

    /**
     * @return NullTransaction
     */
    public static function start() : self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function commit(): void
    {
        if ($this->isRollbackOnly) {
            $this->rollback();

            return;
        }

        $this->status = self::COMMITTED;
    }

    /**
     * @return bool
     */
    public function isCommitted() : bool
    {
        return $this->status == self::COMMITTED;
    }

    /**
     * @return bool
     */
    public function isRolledBack() : bool
    {
        return $this->status == self::ROLLED_BACK;
    }

    /**
     * @inheritDoc
     */
    public function rollback(): void
    {
        $this->status = self::ROLLED_BACK;
    }

    /**
     * @inheritDoc
     */
    public function setRollbackOnly(): void
    {
        $this->isRollbackOnly = true;
    }
}