<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\DynamicChannel\ReceivingStrategy;

use Ecotone\Messaging\Channel\DynamicChannel\ChannelReceivingStrategy;
use Ecotone\Messaging\Config\ConfigurationException;

/**
 * licence Enterprise
 */
final class NoReceivingStrategy implements ChannelReceivingStrategy
{
    public function __construct(private string $channelName)
    {

    }

    public function decide(): string
    {
        throw new ConfigurationException("Message Channel {$this->channelName} has no receiving strategy. Please set up receiving strategy before receiving message from it.");
    }
}
