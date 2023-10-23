<?php

namespace Ecotone\Messaging\Config\Container;

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
