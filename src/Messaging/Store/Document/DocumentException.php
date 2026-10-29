<?php

namespace Ecotone\Messaging\Store\Document;

use Ecotone\Messaging\MessagingException;

/**
 * licence Apache-2.0
 */
class DocumentException extends MessagingException
{
    protected static function errorCode(): int
    {
        return 2000;
    }
}
