<?php

namespace SimplyCodedSoftware\Messaging\Support;

use SimplyCodedSoftware\Messaging\MessagingException;

/**
 * Class InvalidArgumentException
 * @package SimplyCodedSoftware\Messaging\Support
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
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