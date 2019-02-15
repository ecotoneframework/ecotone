<?php

namespace SimplyCodedSoftware\Messaging\Transaction;

use SimplyCodedSoftware\Messaging\Message;

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
     * @param Message $requestRequestMessage
     * @return void
     */
    public function commit(Message $requestRequestMessage) : void;

    /**
     * Roll back the transaction
     * @param Message $requestMessage
     * @return void
     */
    public function rollback(Message $requestMessage) : void;

    /**
     * Changes the transaction that the only possible outcome of the transaction is to roll back the transaction
     * @throws TransactionException
     */
    public function setRollbackOnly() : void;
}