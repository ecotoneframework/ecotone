<?php

namespace Test\Ecotone\Messaging\Fixture\Channel;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Message;

/**
 * licence Apache-2.0
 */
class PollingChannelThrowingException extends QueueChannel
{
    private mixed $exception;

    public function receive(): ?Message
    {
        throw $this->exception;
    }

    public function withException(mixed $exception): self
    {
        $this->exception = $exception;

        return $this;
    }

}
