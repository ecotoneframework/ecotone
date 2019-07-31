<?php

namespace Ecotone\Messaging\Endpoint;

use Ecotone\Messaging\MessagingException;

/**
 * Class NoConsumerFactoryException
 * @package Ecotone\Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class NoConsumerFactoryForBuilderException extends MessagingException
{
    const NO_CONSUMER_FACTORY_FOR_BUILDER_EXCEPTION = 130;

    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return self::NO_CONSUMER_FACTORY_FOR_BUILDER_EXCEPTION;
    }
}