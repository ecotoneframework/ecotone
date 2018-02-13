<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler;

use SimplyCodedSoftware\IntegrationMessaging\MessagingException;

/**
 * Class ReferenceNotFoundException
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler
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