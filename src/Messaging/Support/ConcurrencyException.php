<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Support;

use Ecotone\Messaging\MessagingException;

final class ConcurrencyException extends MessagingException
{
    protected static function errorCode(): int
    {
        return self::MESSAGE_HANDLING_EXCEPTION;
    }
}
