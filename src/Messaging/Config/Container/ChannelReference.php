<?php

namespace Ecotone\Messaging\Config\Container;

/**
 * licence Apache-2.0
 */
class ChannelReference extends Reference
{
    public function __construct(private string $channelName)
    {
        parent::__construct('channel-'.$channelName);
    }

    public function getChannelName(): string
    {
        return $this->channelName;
    }
}
