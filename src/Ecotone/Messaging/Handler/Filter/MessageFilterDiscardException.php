<?php

namespace Ecotone\Messaging\Handler\Filter;

use Ecotone\Messaging\MessagingException;

/**
 * Class MessageFilterDiscardException
 * @package Ecotone\Messaging\Handler\Filter
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