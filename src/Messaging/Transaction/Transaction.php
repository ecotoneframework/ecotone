<?php

namespace SimplyCodedSoftware\Messaging\Transaction;

/**
 * Interface Transaction
 * @package SimplyCodedSoftware\Messaging\Transaction
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface Transaction
{
    /**
     * Complete the transaction represented by this object
     *
     * @return void
     * @throws TransactionException
     */
    public function commit() : void;

    /**
     * Roll back the transaction
     * @throws TransactionException
     */
    public function rollback() : void;

    /**
     * Changes the transaction that the only possible outcome of the transaction is to roll back the transaction
     * @throws TransactionException
     */
    public function setRollbackOnly() : void;

    /**
     * Is transaction running
     *
     * @return bool
     */
    public function isActive() : bool;

    /**
     * @return bool
     */
    public function isCommitted() : bool;

    /**
     * @return bool
     */
    public function isRolledBack() : bool;

    /**
     * @return bool
     */
    public function isMarkedToRollback() : bool;
}