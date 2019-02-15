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
    private $originalMessage;

    /**
     * @param \Throwable $exception
     * @param Message $failedMessage
     * @return ErrorMessage
     */
    public static function createWithOriginalMessage(\Throwable $exception, Message $failedMessage) : self
    {
        /** @var ErrorMessage $errorMessage */
        $errorMessage = self::create($exception, MessageHeaders::createEmpty());
        $errorMessage->setFailedMessage($failedMessage);

        return $errorMessage;
    }

    /**
     * @return Message|null
     */
    public function getFailedMessage() : ?Message
    {
        return $this->originalMessage;
    }

    /**
     * @param Message $message
     */
    private function setFailedMessage(Message $message) : void
    {
        $this->originalMessage = $message;
    }
}