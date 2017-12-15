<?php

namespace Messaging\Handler;

use Messaging\MessagingException;

/**
 * Class UnresolveChannelException
 * @package Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DestinationResolutionException extends MessagingException
{
    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return self::DESTINATION_RESOLUTION_EXCEPTION;
    }
}