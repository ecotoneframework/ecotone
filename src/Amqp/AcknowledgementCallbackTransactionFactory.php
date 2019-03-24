<?php

namespace SimplyCodedSoftware\Amqp;

use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\Transaction\Transaction;
use SimplyCodedSoftware\Messaging\Transaction\TransactionFactory;

/**
 * Class AcknowledgementCallbackTransactionFactory
 * @package SimplyCodedSoftware\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AcknowledgementCallbackTransactionFactory implements TransactionFactory
{
    /**
     * @inheritDoc
     */
    public function begin(Message $message): Transaction
    {
        return new AcknowledgeTransaction($message->getHeaders()->get(AmqpHeader::HEADER_ACKNOWLEDGE));
    }
}