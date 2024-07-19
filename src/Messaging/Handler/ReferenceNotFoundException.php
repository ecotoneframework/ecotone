<?php

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\MessagingException;

/**
 * Class ReferenceNotFoundException
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class ReferenceNotFoundException extends MessagingException
{
    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return self::REFERENCE_NOT_FOUND_EXCEPTION;
    }
}
