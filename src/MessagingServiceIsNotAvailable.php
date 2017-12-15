<?php

namespace Messaging;

/**
 * Class MessagingServiceIsNotAvailable
 * @package Messaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessagingServiceIsNotAvailable extends MessagingException
{
    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return self::MESSAGING_SERVICE_NOT_AVAILABLE_EXCEPTION;
    }
}