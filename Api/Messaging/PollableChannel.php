<?php

declare(strict_types=1);

namespace Ecotone\Api\Messaging;

/**
 * licence Apache-2.0
 */
interface PollableChannel extends MessageChannel, MessagePoller
{
    public function receive(): ?Message;
}
