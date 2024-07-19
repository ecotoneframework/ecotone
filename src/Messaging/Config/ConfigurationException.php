<?php

namespace Ecotone\Messaging\Config;

use Ecotone\Messaging\MessagingException;

/**
 * Class ConfigurationException
 * @package Ecotone\Messaging\Config
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class ConfigurationException extends MessagingException
{
    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return self::CONFIGURATION_EXCEPTION;
    }
}
