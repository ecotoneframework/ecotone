<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Transaction;

/**
 * Interface TransactionManager
 * @package SimplyCodedSoftware\IntegrationMessaging\Transaction
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