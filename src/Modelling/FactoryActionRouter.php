<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Message;

/**
 * licence Apache-2.0
 */
class FactoryActionRouter
{
    private string $channelName;

    public function __construct(string $channelName)
    {
        $this->channelName = $channelName;
    }

    public function route(Message $message): void
    {
    }
}
