<?php

namespace Messaging\Exception\Message;

use Messaging\Exception\MessagingException;
use Messaging\Exception\MessagingExceptionCode;

/**
 * Class MessageHeaderDoesNotExistsException
 * @package Messaging\Exception\Message
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageHeaderDoesNotExistsException extends MessagingException
{
    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return MessagingExceptionCode::MESSAGE_HEADER_DOES_NOT_EXISTS;
    }
}