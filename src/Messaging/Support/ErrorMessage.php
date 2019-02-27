<?php

namespace SimplyCodedSoftware\Messaging\Support;

use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageHeaders;

/**
 * Class ErrorMessage where payload is thrown exception
 * @package SimplyCodedSoftware\Messaging\Support
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class ErrorMessage extends GenericMessage
{
    /**
     * @var Message|null
     */
    private $failedMessage;
    /**
     * @var Message|null
     */
    private $originalMessage;

    /**
     * @param \Throwable $exception
     * @param Message $failedMessage
     * @return ErrorMessage
     */
    public static function createWithFailedMessage(\Throwable $exception, Message $failedMessage) : self
    {
        /** @var ErrorMessage $errorMessage */
        $errorMessage = self::create($exception, MessageHeaders::createEmpty());
        $errorMessage->setFailedMessage($failedMessage);

        return $errorMessage;
    }

    /**
     * @param \Throwable $exception
     * @param Message $failedMessage
     * @param Message $originalMessage
     * @return ErrorMessage
     */
    public static function createWithFailedAndOriginalMessage(\Throwable $exception, Message $failedMessage, Message $originalMessage) : self
    {
        /** @var ErrorMessage $errorMessage */
        $errorMessage = self::create($exception, MessageHeaders::createEmpty());
        $errorMessage->setFailedMessage($failedMessage);
        $errorMessage->setOriginalMessage($originalMessage);

        return $errorMessage;
    }

    /**
     * @param \Throwable $exception
     * @param Message $originalMessage
     * @return ErrorMessage
     */
    public static function createWithOriginalMessage(\Throwable $exception, Message $originalMessage) : self
    {
        /** @var ErrorMessage $errorMessage */
        $errorMessage = self::create($exception, MessageHeaders::createEmpty());
        $errorMessage->setOriginalMessage($originalMessage);

        return $errorMessage;
    }

    /**
     * @param Message $originalMessage
     * @return ErrorMessage
     */
    public function extendWithOriginalMessage(Message $originalMessage) : self
    {
        return self::createWithFailedAndOriginalMessage($this->getPayload(), $this->failedMessage, $originalMessage);
    }

    /**
     * @return \Throwable
     */
    public function getException() : \Throwable
    {
        return $this->getPayload();
    }

    /**
     * @return Message|null
     */
    public function getFailedMessage() : ?Message
    {
        return $this->failedMessage;
    }

    /**
     * @return Message|null
     */
    public function getOriginalMessage(): ?Message
    {
        return $this->originalMessage;
    }

    /**
     * @param Message $message
     */
    private function setFailedMessage(Message $message) : void
    {
        $this->failedMessage = $message;
    }

    /**
     * @param Message $message
     */
    private function setOriginalMessage(Message $message) : void
    {
        $this->originalMessage = $message;
    }
}