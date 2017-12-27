<?php

namespace Messaging\Handler;

use Messaging\Message;
use Messaging\MessagingException;
use Messaging\Support\ErrorMessage;

/**
 * Class MessageHandlingException
 * @package Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class MessageHandlingException extends MessagingException
{
    /**
     * @param ErrorMessage $errorMessage
     * @return MessageHandlingException
     */
    public static function fromErrorMessage(ErrorMessage $errorMessage) : self
    {
        return new self($errorMessage->getPayload(), $errorMessage->getOriginalMessage());
    }

    /**
     * @param \Throwable $cause
     * @param Message $errorMessage
     */
    public static function fromOtherException(\Throwable $cause, Message $errorMessage)
    {
        $messageHandlingException = self::createWithFailedMessage($cause->getMessage(), $errorMessage);

        $messageHandlingException->setCausationException($cause);
    }

    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return self::MESSAGE_HANDLING_EXCEPTION;
    }
}