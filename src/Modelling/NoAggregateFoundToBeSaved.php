<?php

declare(strict_types=1);

namespace Ecotone\Modelling;

use Ecotone\Messaging\MessagingException;

/**
 * licence Apache-2.0
 */
final class NoAggregateFoundToBeSaved extends MessagingException
{
    public const ERROR_CODE = 1003;

    protected static function errorCode(): int
    {
        return self::ERROR_CODE;
    }
}
