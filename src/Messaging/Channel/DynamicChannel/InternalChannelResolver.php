<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\DynamicChannel;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\PollableChannel;

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
                return $internalChannel['channel'];
            }
        }

        return $this->channelResolver->resolve($channelName);
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
