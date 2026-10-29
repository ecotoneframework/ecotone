<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\DynamicChannel;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Support\Assert;

/**
 * licence Apache-2.0
 */
final class InternalChannelResolver implements ChannelResolver
{
    /**
     * @param array{channel: MessageChannel[]|PollableChannel[], name: string} $internalChannels
     */
    public function __construct(
        private ChannelResolver $channelResolver,
        private array $internalChannels,
    ) {
    }

    public function resolve(MessageChannel|string $channelName): MessageChannel
    {
        foreach ($this->internalChannels as $internalChannel) {
            if ($internalChannel['name'] === $channelName) {
                Assert::isTrue($internalChannel['channel'] instanceof PollableChannel, "Dynamic Message Channels can only be used together with Pollable Channels. Internal channel {$channelName} is not pollable");

                return $internalChannel['channel'];
            }
        }

        $messageChannel = $this->channelResolver->resolve($channelName);
        Assert::isTrue($messageChannel instanceof PollableChannel, "Dynamic Message Channels can only be used together with Pollable Channels. Channel {$channelName} is not pollable");

        return $messageChannel;
    }

    public function hasChannelWithName(string $channelName): bool
    {
        foreach ($this->internalChannels as $internalChannel) {
            if ($internalChannel['name'] === $channelName) {
                return true;
            }
        }

        return $this->channelResolver->hasChannelWithName($channelName);
    }

}
