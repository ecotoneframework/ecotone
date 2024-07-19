<?php

namespace Ecotone\Modelling\MessageHandling\Distribution;

use Ecotone\Messaging\MessagingException;

/**
 * licence Apache-2.0
 */
class RoutingKeyIsNotDistributed extends MessagingException
{
    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return self::DISTRIBUTED_KEY_IS_NOT_AVAILABLE;
    }
}
