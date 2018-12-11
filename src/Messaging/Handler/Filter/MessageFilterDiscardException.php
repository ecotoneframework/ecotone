<?php

namespace SimplyCodedSoftware\Messaging\Handler\Filter;

use SimplyCodedSoftware\Messaging\MessagingException;

/**
 * Class MessageFilterDiscardException
 * @package SimplyCodedSoftware\Messaging\Handler\Filter
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