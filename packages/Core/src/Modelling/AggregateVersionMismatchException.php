<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\MessagingException;

/**
 * Class AggregateVersionMismatchException
 * @package Ecotone\Modelling
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