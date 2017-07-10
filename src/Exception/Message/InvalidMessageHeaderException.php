<?php

namespace Messaging\Exception\Message;

use Messaging\Exception\MessagingException;

/**
 * Class InvalidMessageHeaderException
 * @package Messaging\Exception\Message
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InvalidMessageHeaderException extends MessagingException
{
    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return self::INVALID_MESSAGE_HEADER;
    }
}