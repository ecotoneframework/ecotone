<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Transaction;

/**
 * Class AcknowledgementCallbackTransactionFactory
 * @package SimplyCodedSoftware\Messaging\Transaction
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AcknowledgementCallbackTransactionFactory implements TransactionFactory
{
    /**
     * @var string
     */
    private $acknowledgementCallbackHeaderName;

    /**
     * AcknowledgementCallbackTransaction constructor.
     * @param string $acknowledgementCallbackHeaderName
     */
    private function __construct(string $acknowledgementCallbackHeaderName)
    {
        $this->acknowledgementCallbackHeaderName = $acknowledgementCallbackHeaderName;
    }

    /**
     * @param string $acknowledgementCallbackHeaderName
     * @return self
     */
    public static function createWith(string $acknowledgementCallbackHeaderName) : self
    {
        return new self($acknowledgementCallbackHeaderName);
    }

    /**
     * @inheritDoc
     */
    public function begin(): Transaction
    {
        return AcknowledgementCallbackTransaction::createWith($this->acknowledgementCallbackHeaderName);
    }
}