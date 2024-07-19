<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\DynamicChannel\ReceivingStrategy;

use Ecotone\Messaging\Channel\DynamicChannel\ChannelReceivingStrategy;

/**
 * licence Enterprise
 */
final class RoundRobinReceivingStrategy implements ChannelReceivingStrategy
{
    public function __construct(
        private array $channelNames,
        private int   $currentChannelIndex = 0,
    ) {

    }

    public function decide(): string
    {
        $channelName = $this->channelNames[$this->currentChannelIndex];

        $this->currentChannelIndex++;

        if ($this->currentChannelIndex >= count($this->channelNames)) {
            $this->currentChannelIndex = 0;
        }

        return $channelName;
    }
}
