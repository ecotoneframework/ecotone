<?php

namespace Ecotone\EventSourcing;

use Ecotone\Messaging\MessagingException;

final class ProjectionNotFoundException extends MessagingException
{
    protected static function errorCode(): int
    {
        return 3001;
    }
}
