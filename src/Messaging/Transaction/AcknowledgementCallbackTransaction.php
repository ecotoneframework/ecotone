<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Transaction;

use SimplyCodedSoftware\Messaging\Endpoint\MessageDrivenChannelAdapter\AcknowledgementCallback;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class AcknowledgementCallbackTransaction
 * @package SimplyCodedSoftware\Messaging\Transaction
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AcknowledgementCallbackTransaction implements Transaction
{
    /**
     * @var bool
     */
    private $rollbackOnly = false;
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
     * @return AcknowledgementCallbackTransaction
     */
    public static function createWith(string $acknowledgementCallbackHeaderName) : self
    {
        return new self($acknowledgementCallbackHeaderName);
    }

    /**
     * @inheritDoc
     */
    public function commit(Message $requestMessage): void
    {
        if ($this->rollbackOnly) {
            $this->rollback($requestMessage);

            return;
        }

        $this->getAcknowledgeCallback($requestMessage)->accept();
    }

    /**
     * @inheritDoc
     */
    public function rollback(Message $requestMessage): void
    {
        $this->getAcknowledgeCallback($requestMessage)->requeue();
    }

    /**
     * @inheritDoc
     */
    public function setRollbackOnly(): void
    {
        $this->rollbackOnly = true;
    }

    /**
     * @param Message $requestMessage
     * @return AcknowledgementCallback
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function getAcknowledgeCallback(Message $requestMessage): AcknowledgementCallback
    {
        if (!$requestMessage->getHeaders()->containsKey($this->acknowledgementCallbackHeaderName)) {
            throw InvalidArgumentException::create("Message has acknowledge information");
        }

        /** @var AcknowledgementCallback $acknowledge */
        $acknowledge = $requestMessage->getHeaders()->get($this->acknowledgementCallbackHeaderName);
        return $acknowledge;
    }
}