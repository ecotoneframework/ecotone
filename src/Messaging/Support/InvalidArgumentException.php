<?php

namespace Ecotone\Messaging\Support;

use Ecotone\Messaging\MessagingException;

/**
 * Class InvalidArgumentException
 * @package Ecotone\Messaging\Support
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class InvalidArgumentException extends MessagingException
{
    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return self::INVALID_ARGUMENT_EXCEPTION;
    }
}
