<?php

namespace SimplyCodedSoftware\Messaging\Handler\Enricher;

use SimplyCodedSoftware\Messaging\MessagingException;

/**
 * Class EnrichException
 * @package SimplyCodedSoftware\Messaging\Handler\Enricher
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EnrichException extends MessagingException
{
    public const ERROR_CODE = 400;

    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return self::ERROR_CODE;
    }
}