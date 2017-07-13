<?php

namespace Messaging\Exception;

use Messaging\MessagingException;

/**
 * Class MessageSendException
 * @package Messaging\Exception
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageDeliveryException extends MessagingException
{
    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return self::MESSAGE_SEND_EXCEPTION;
    }
}