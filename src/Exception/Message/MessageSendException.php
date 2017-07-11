<?php

namespace Messaging\Exception\Message;

use Messaging\Exception\MessagingException;

/**
 * Class MessageSendException
 * @package Messaging\Exception\Message
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageSendException extends MessagingException
{
    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return self::MESSAGE_SEND_EXCEPTION;
    }
}