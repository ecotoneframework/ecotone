<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Filter;

use SimplyCodedSoftware\IntegrationMessaging\MessagingException;

/**
 * Class MessageFilterDiscardException
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Filter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageFilterDiscardException extends MessagingException
{
    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return self::MESSAGE_FILTER_THROW_EXCEPTION_ON_DISCARD;
    }
}