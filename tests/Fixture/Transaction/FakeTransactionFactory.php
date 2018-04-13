<?php

namespace Fixture\Transaction;

use SimplyCodedSoftware\IntegrationMessaging\Transaction\Transaction;
use SimplyCodedSoftware\IntegrationMessaging\Transaction\TransactionFactory;

/**
 * Class FakeTransactionManager
 * @package Fixture\Transaction
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
    public function begin(): Transaction
    {
        return $this->transactionToReturn ? $this->transactionToReturn : FakeTransaction::begin();
    }
}