<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\DynamicChannel\SendingStrategy;

use Ecotone\Messaging\Channel\DynamicChannel\ChannelSendingStrategy;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Message;

/**
 * licence Enterprise
 */
final class NoSendingStrategy implements ChannelSendingStrategy
{
    public function __construct(private string $channelName)
    {

    }

    public function decideFor(Message $message): string
    {
        throw ConfigurationException::create("Message Channel {$this->channelName} has no sending strategy. Please set up sending strategy before sending message to it.");
    }
}
