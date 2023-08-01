<?php

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\MessagingException;

/**
 * Class MessageHandlingException
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class MessageHandlingException extends MessagingException
{
    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return self::MESSAGE_HANDLING_EXCEPTION;
    }
}
