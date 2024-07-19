<?php

namespace Ecotone\Messaging\Endpoint\PollingConsumer;

use RuntimeException;

/**
 * licence Apache-2.0
 */
class ConnectionException extends RuntimeException
{
    public static function connectionRetryMessage(int $retryCount, int $delayTimeMs): self
    {
        return new self("Retrying to connect to the Message Channel. Current number of retries: {$retryCount}, Message Consumer will try to reconnect in {$delayTimeMs}ms.", );
    }
}
