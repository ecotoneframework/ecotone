<?php

namespace Ecotone\DomainModel;

use Ecotone\Messaging\MessagingException;

/**
 * Class AggregateVersionMismatchException
 * @package Ecotone\DomainModel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AggregateVersionMismatchException extends MessagingException
{
    public const AGGREGATE_VERSION_MISMATCH = 1001;

    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return self::AGGREGATE_VERSION_MISMATCH;
    }
}