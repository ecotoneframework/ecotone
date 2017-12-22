<?php

namespace Messaging;

/**
 * Class RunTimeMessagingException
 * @package Messaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class RunTimeMessagingException extends MessagingException
{
    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return self::RUN_TIME_MESSAGING_EXCEPTION;
    }
}