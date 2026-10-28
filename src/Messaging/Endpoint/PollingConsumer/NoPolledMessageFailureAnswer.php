<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\PollingConsumer;

use Ecotone\Api\Messaging\Message;
use Throwable;

/**
 * licence Apache-2.0
 */
final class NoPolledMessageFailureAnswer implements PolledMessageFailureAnswer
{
    public function answered(Message $failedMessage, Throwable $cause): bool
    {
        return false;
    }
}
