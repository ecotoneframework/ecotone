<?php

namespace SimplyCodedSoftware\Messaging\Handler;

use SimplyCodedSoftware\Messaging\MessagingException;

/**
 * Class ReferenceNotFoundException
 * @package SimplyCodedSoftware\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
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