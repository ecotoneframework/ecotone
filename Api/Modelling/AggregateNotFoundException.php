<?php

namespace Ecotone\Api\Modelling;

use Ecotone\Messaging\MessagingException;

/**
 * licence Apache-2.0
 */
class AggregateNotFoundException extends MessagingException
{
    public const AGGREGATE_NOT_FOUND_EXCEPTION = 1000;

    protected static function errorCode(): int
    {
        return self::AGGREGATE_NOT_FOUND_EXCEPTION;
    }
}
