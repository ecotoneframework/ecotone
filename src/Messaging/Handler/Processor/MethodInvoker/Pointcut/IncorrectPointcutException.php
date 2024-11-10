<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Pointcut;

use Ecotone\Messaging\MessagingException;

/**
 * licence Apache-2.0
 */
final class IncorrectPointcutException extends MessagingException
{
    protected static function errorCode(): int
    {
        return 100;
    }
}
