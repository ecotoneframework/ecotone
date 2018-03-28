<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher;

use SimplyCodedSoftware\IntegrationMessaging\MessagingException;

/**
 * Class EnrichException
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher
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