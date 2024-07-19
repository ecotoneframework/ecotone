<?php

namespace Ecotone\Messaging;

/**
 * Class MessagingServiceIsNotAvailable
 * @package Ecotone\Messaging
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
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
