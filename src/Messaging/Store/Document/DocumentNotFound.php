<?php

namespace Ecotone\Messaging\Store\Document;

/**
 * licence Apache-2.0
 */
final class DocumentNotFound extends DocumentException
{
    protected static function errorCode(): int
    {
        return 2001;
    }
}
