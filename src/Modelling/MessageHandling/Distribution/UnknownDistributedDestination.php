<?php

declare(strict_types=1);

namespace Ecotone\Modelling\MessageHandling\Distribution;

use Ecotone\Messaging\MessagingException;

/**
 * licence Enterprise
 */
final class UnknownDistributedDestination extends MessagingException
{
    protected static function errorCode(): int
    {
        return self::DISTRIBUTED_DESTINATION_NOT_FOUND;
    }
}
