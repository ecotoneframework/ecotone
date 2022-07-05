<?php


namespace Ecotone\Messaging\Store\Document;


use Ecotone\Messaging\MessagingException;

class DocumentException extends MessagingException
{
    protected static function errorCode(): int
    {
        return 2000;
    }
}