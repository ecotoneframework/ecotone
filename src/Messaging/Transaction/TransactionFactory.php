<?php

namespace Ecotone\Messaging\Transaction;

use Ecotone\Messaging\Message;

/**
 * Interface TransactionManager
 * @package Ecotone\Messaging\Transaction
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface TransactionFactory
{
    /**
     * Create a new transaction and associate it with current process
     *
     * @param Message $requestMessage
     * @return Transaction
     */
    public function begin(Message $requestMessage) : Transaction;
}