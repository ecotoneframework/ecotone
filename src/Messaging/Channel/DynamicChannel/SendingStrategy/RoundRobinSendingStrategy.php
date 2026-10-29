<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\DynamicChannel\SendingStrategy;

use Ecotone\Messaging\Channel\DynamicChannel\ChannelSendingStrategy;
use Ecotone\Messaging\Message;

/**
 * licence Enterprise
 */
final class RoundRobinSendingStrategy implements ChannelSendingStrategy
{
    public function __construct(
        private array $channelNames,
        private int   $currentChannelIndex = 0,
    ) {

    }

    public function decideFor(Message $message): string
    {
        $channelName = $this->channelNames[$this->currentChannelIndex];

        $this->currentChannelIndex++;

        if ($this->currentChannelIndex >= count($this->channelNames)) {
            $this->currentChannelIndex = 0;
        }

        return $channelName;
    }
}
