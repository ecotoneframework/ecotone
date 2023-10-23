<?php

namespace Ecotone\Messaging;

interface MessagePoller
{
    /**
     * Receive with timeout
     * Tries to receive message till time out passes
     */
    public function receiveWithTimeout(int $timeoutInMilliseconds): ?Message;
}
