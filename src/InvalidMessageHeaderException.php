<?php

namespace Messaging;

/**
 * Class InvalidMessageHeaderException
 * @package Messaging\Exception
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InvalidMessageHeaderException extends MessagingException
{
    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return self::INVALID_MESSAGE_HEADER_EXCEPTION;
    }
}