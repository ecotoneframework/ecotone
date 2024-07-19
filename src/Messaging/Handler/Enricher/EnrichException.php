<?php

namespace Ecotone\Messaging\Handler\Enricher;

use Ecotone\Messaging\MessagingException;

/**
 * Class EnrichException
 * @package Ecotone\Messaging\Handler\Enricher
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
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
