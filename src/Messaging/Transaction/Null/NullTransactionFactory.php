<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Transaction\Null;

use SimplyCodedSoftware\Messaging\Transaction\Transaction;
use SimplyCodedSoftware\Messaging\Transaction\TransactionFactory;

/**
 * Class NullTransactionFactory
 * @package SimplyCodedSoftware\Messaging\Transaction\Null
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class NullTransactionFactory implements TransactionFactory
{
    /**
     * @var Transaction
     */
    private $predefinedTransaction = null;

    /**
     * NullTransactionFactory constructor.
     * @param Transaction|null $nullTransaction
     */
    private function __construct(?Transaction $nullTransaction)
    {
        $this->predefinedTransaction = $nullTransaction;
    }

    /**
     * @return NullTransactionFactory
     */
    public static function create() : self
    {
        return new self(null);
    }

    /**
     * @param Transaction $transaction
     * @return NullTransactionFactory
     */
    public static function createWithPredefinedTransaction(Transaction $transaction) : self
    {
        return new self($transaction);
    }

    /**
     * @inheritDoc
     */
    public function begin(): Transaction
    {
        return NullTransaction::start();
    }
}