<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Endpoint;

use SimplyCodedSoftware\IntegrationMessaging\MessagingException;

/**
 * Class NoConsumerFactoryException
 * @package SimplyCodedSoftware\IntegrationMessaging\Endpoint
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