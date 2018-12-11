<?php

namespace SimplyCodedSoftware\Messaging\Transaction;

/**
 * Interface TransactionManager
 * @package SimplyCodedSoftware\Messaging\Transaction
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface TransactionFactory
{
    /**
     * Create a new transaction and associate it with current process
     *
     * @return Transaction
     */
    public function begin() : Transaction;
}