<?php

namespace Ecotone\Messaging\Transaction;

use Ecotone\Messaging\Message;

/**
 * Interface TransactionManager
 * @package Ecotone\Messaging\Transaction
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface TransactionFactory
{
    /**
     * Create a new transaction and associate it with current process
     *
     * @param Message $requestMessage
     * @return Transaction
     */
    public function begin(Message $requestMessage): Transaction;
}
