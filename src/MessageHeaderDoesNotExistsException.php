<?php

namespace Messaging;

use Messaging\MessagingException;
use Messaging\MessagingExceptionCode;

/**
 * Class MessageHeaderDoesNotExistsException
 * @package Messaging\Exception
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