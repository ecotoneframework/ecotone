<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Support;

use Ecotone\Messaging\MessagingException;

/**
 * licence Enterprise
 */
final class LicensingException extends MessagingException
{
    public static function create(string $message, ?int $errorCode = null): self
    {
        return new static($message . ' To purchase licence visit https://ecotone.tech.', is_null($errorCode) ? static::errorCode() : $errorCode);
    }

    protected static function errorCode(): int
    {
        return self::LICENSE_EXCEPTION;
    }
}
