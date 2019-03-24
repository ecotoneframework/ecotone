<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Transaction;

use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\Transaction\Transaction;
use SimplyCodedSoftware\Messaging\Transaction\TransactionFactory;

/**
 * Class FakeTransactionManager
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Transaction
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class FakeTransactionFactory implements TransactionFactory
{
    /**
     * @var null|Transaction
     */
    private $transactionToReturn = null;

    /**
     * FakeTransactionManager constructor.
     *
     * @param Transaction|null $transaction
     */
    private function __construct(?Transaction $transaction)
    {
        $this->transactionToReturn = $transaction;
    }

    /**
     * @return FakeTransactionFactory
     */
    public static function create() : self
    {
        return new self(null);
    }

    /**
     * @param Transaction $transaction
     *
     * @return FakeTransactionFactory
     */
    public static function createWith(Transaction $transaction) : self
    {
        return new self($transaction);
    }

    /**
     * @inheritDoc
     */
    public function begin(Message $message): Transaction
    {
        $this->transactionToReturn = $this->transactionToReturn ? $this->transactionToReturn : FakeTransaction::begin();


        return $this->transactionToReturn;
    }

    public function getCurrentTransaction() : ?Transaction
    {
        return $this->transactionToReturn;
    }
}