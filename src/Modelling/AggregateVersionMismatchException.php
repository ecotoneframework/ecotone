<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\MessagingException;

/**
 * Class AggregateVersionMismatchException
 * @package Ecotone\Modelling
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
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
