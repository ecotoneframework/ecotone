<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Pointcut;

use Ecotone\Messaging\MessagingException;

final class IncorrectPointcutException extends MessagingException
{
    protected static function errorCode(): int
    {
        return 100;
    }
}
