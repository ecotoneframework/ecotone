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
        if (!$errorMessage->getOriginalMessage()) {
            /** @var \Throwable $throwable */
            $throwable = $errorMessage->getPayload();

            $messagingException = self::create($throwable->getMessage());
            $messagingException->setCausationException($errorMessage->getPayload());

            return $messagingException;
        }

        return self::fromOtherException($errorMessage->getPayload(), $errorMessage->getOriginalMessage());
    }

    /**
     * @param \Throwable $cause
     * @param Message $errorMessage
     * @return MessageHandlingException|static
     */
    public static function fromOtherException(\Throwable $cause, Message $errorMessage) : self
    {
        $messageHandlingException = self::createWithFailedMessage($cause->getMessage(), $errorMessage);

        $messageHandlingException->setCausationException($cause);

        return $messageHandlingException;
    }

    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return self::MESSAGE_HANDLING_EXCEPTION;
    }
}