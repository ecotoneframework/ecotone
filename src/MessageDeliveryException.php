<?php

namespace Messaging;

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
        return self::MESSAGE_DELIVERY_EXCEPTION;
    }
}