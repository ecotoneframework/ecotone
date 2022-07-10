<?php


namespace Ecotone\Messaging\Conversion;


use Ecotone\Messaging\MessagingException;

class ConversionException extends MessagingException
{
    protected static function errorCode(): int
    {
        return 2001;
    }
}