<?php

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\ErrorMessage;

/**
 * Class MessageHandlingException
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class MessageHandlingException extends MessagingException
{
    /**
     * @param \Throwable $cause
     * @param Message $originalMessage
     * @return MessageHandlingException|static
     */
    public static function fromOtherException(\Throwable $cause, Message $originalMessage) : \Ecotone\Messaging\MessagingException
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