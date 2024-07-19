<?php

namespace Ecotone\Messaging\Transaction;

use RuntimeException;

/**
 * Class TransactionException
 * @package Ecotone\Messaging\Transaction
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class TransactionException extends RuntimeException
{
    /**
     * @param string $message
     *
     * @return TransactionException
     */
    public static function createWith(string $message): TransactionException
    {
        return new self($message);
    }
}
