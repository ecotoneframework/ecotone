<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\DynamicChannel;

use Ecotone\Api\ExtensionObject\DynamicMessageChannelBuilder;
use Ecotone\Api\ExtensionObject\MessageChannelBuilder;

/**
 * licence Apache-2.0
 */
final class StreamsReceivedByDynamicChannel
{
    /**
     * @param array<string, MessageChannelBuilder> $channelsOfTheApplication
     * @param DynamicMessageChannelBuilder[] $dynamicChannelsAlreadyFollowed
     * @return string[]
     */
    public static function of(DynamicMessageChannelBuilder $dynamicChannel, array $channelsOfTheApplication, array $dynamicChannelsAlreadyFollowed = []): array
    {
        $dynamicChannelsAlreadyFollowed[] = $dynamicChannel;
        $receivedFrom = array_values($dynamicChannel->internalMessageChannels);
        if ($dynamicChannel->hasCustomReceivingStrategy()) {
            $receivedFrom = [...$receivedFrom, ...array_values($channelsOfTheApplication)];
        }
        foreach ($dynamicChannel->receivingChannelNames as $receivingChannelName) {
            if (isset($channelsOfTheApplication[$receivingChannelName])) {
                $receivedFrom[] = $channelsOfTheApplication[$receivingChannelName];
            }
        }

        $streams = [];
        foreach ($receivedFrom as $channel) {
            if (in_array($channel, $dynamicChannelsAlreadyFollowed, true)) {
                continue;
            }
            if ($channel instanceof DynamicMessageChannelBuilder) {
                $streams = [...$streams, ...self::of($channel, $channelsOfTheApplication, $dynamicChannelsAlreadyFollowed)];

                continue;
            }
            if ($channel->isStreamingChannel()) {
                $streams[] = $channel->getMessageChannelName();
            }
        }

        return array_values(array_unique($streams));
    }
}
