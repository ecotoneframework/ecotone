<?php

namespace Messaging\Config;

use Messaging\MessagingException;

/**
 * Class ConfigurationException
 * @package Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ConfigurationException extends MessagingException
{
    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return self::CONFIGURATION_IS_WRONG;
    }
}