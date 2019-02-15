<?php

namespace SimplyCodedSoftware\Messaging\Handler;

use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessagingException;
use SimplyCodedSoftware\Messaging\Support\ErrorMessage;

/**
 * Class MessageHandlingException
 * @package SimplyCodedSoftware\Messaging\Handler
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
        if (!$errorMessage->getFailedMessage()) {
            /** @var \Throwable $throwable */
            $throwable = $errorMessage->getPayload();

            $messagingException = self::create($throwable->getMessage());
            $messagingException->setCausationException($errorMessage->getPayload());

            return $messagingException;
        }

        return self::fromOtherException($errorMessage->getPayload(), $errorMessage->getFailedMessage());
    }

    /**
     * @param \Throwable $cause
     * @param Message $originalMessage
     * @return MessageHandlingException|static
     */
    public static function fromOtherException(\Throwable $cause, Message $originalMessage) : self
    {
        $messageHandlingException = self::createWithFailedMessage($cause->getMessage(), $originalMessage);

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