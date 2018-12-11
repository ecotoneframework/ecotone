<?php

namespace SimplyCodedSoftware\Messaging;

/**
 * Class MessagingServiceIsNotAvailable
 * @package SimplyCodedSoftware\Messaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessagingServiceIsNotAvailableException extends MessagingException
{
    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return self::MESSAGING_SERVICE_NOT_AVAILABLE_EXCEPTION;
    }
}