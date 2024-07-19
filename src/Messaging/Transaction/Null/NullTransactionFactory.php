<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Transaction\Null;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\Transaction\Transaction;
use Ecotone\Messaging\Transaction\TransactionFactory;

/**
 * Class NullTransactionFactory
 * @package Ecotone\Messaging\Transaction\Null
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class NullTransactionFactory implements TransactionFactory
{
    private ?Transaction $predefinedTransaction;

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
    public static function create(): self
    {
        return new self(null);
    }

    /**
     * @param Transaction $transaction
     * @return NullTransactionFactory
     */
    public static function createWithPredefinedTransaction(Transaction $transaction): self
    {
        return new self($transaction);
    }

    /**
     * @inheritDoc
     */
    public function begin(Message $message): Transaction
    {
        return $this->predefinedTransaction ?? NullTransaction::start();
    }
}
