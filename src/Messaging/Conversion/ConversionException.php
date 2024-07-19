<?php

namespace Ecotone\Messaging\Conversion;

use Ecotone\Messaging\MessagingException;

/**
 * licence Apache-2.0
 */
class ConversionException extends MessagingException
{
    protected static function errorCode(): int
    {
        return 2001;
    }
}
