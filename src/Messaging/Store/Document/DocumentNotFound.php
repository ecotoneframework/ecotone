<?php

namespace Ecotone\Messaging\Store\Document;

final class DocumentNotFound extends DocumentException
{
    protected static function errorCode(): int
    {
        return 2001;
    }
}