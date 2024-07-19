<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\MessagingException;

/**
 * licence Apache-2.0
 */
class NoCorrectIdentifierDefinedException extends MessagingException
{
    public const ERROR_CODE = 1002;

    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return self::ERROR_CODE;
    }
}
