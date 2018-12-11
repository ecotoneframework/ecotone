<?php

namespace SimplyCodedSoftware\Messaging\Transaction;

/**
 * Class TransactionException
 * @package SimplyCodedSoftware\Messaging\Transaction
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TransactionException extends \RuntimeException
{
    /**
     * @param string $message
     *
     * @return TransactionException
     */
    public static function createWith(string $message) : TransactionException
    {
        return new self($message);
    }
}