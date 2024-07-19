<?php

namespace Ecotone\Messaging;

/**
 * licence Apache-2.0
 */
interface MessagePoller
{
    /**
     * Receive with timeout
     * Tries to receive message till time out passes
     */
    public function receiveWithTimeout(int $timeoutInMilliseconds): ?Message;
}
