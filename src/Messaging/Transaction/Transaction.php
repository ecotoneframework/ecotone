<?php

namespace Ecotone\Messaging\Transaction;

/**
 * Interface Transaction
 * @package Ecotone\Messaging\Transaction
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface Transaction
{
    /**
     * Complete the transaction represented by this object
     *
     * @return void
     */
    public function commit(): void;

    /**
     * Roll back the transaction
     * @return void
     */
    public function rollback(): void;

    /**
     * Changes the transaction that the only possible outcome of the transaction is to roll back the transaction
     * @throws TransactionException
     */
    public function setRollbackOnly(): void;
}
