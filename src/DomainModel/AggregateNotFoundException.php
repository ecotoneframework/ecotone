<?php

namespace Ecotone\DomainModel;

use Ecotone\Messaging\MessagingException;

/**
 * Class AggregateNotFoundException
 * @package Ecotone\DomainModel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AggregateNotFoundException extends MessagingException
{
    public const AGGREGATE_NOT_FOUND_EXCEPTION = 1000;

    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return self::AGGREGATE_NOT_FOUND_EXCEPTION;
    }
}