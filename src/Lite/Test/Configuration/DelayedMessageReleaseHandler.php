<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test\Configuration;

use DateTimeInterface;
use Ecotone\Messaging\Channel\DelayableQueueChannel;
use Ecotone\Messaging\Channel\MessageChannelInterceptorAdapter;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Scheduling\TimeSpan;

/**
 * licence Apache-2.0
 */
final class DelayedMessageReleaseHandler
{
    public function releaseMessagesAwaitingFor(string $channelName, int|TimeSpan|DateTimeInterface $timeInMillisecondsOrDateTime, ChannelResolver $channelResolver): void
    {
        if (! $channelResolver->hasChannelWithName($channelName)) {
            return;
        }

        /** @var DelayableQueueChannel|MessageChannelInterceptorAdapter $channel */
        $channel = $channelResolver->resolve($channelName);
        if ($channel instanceof MessageChannelInterceptorAdapter) {
            $channel = $channel->getInternalMessageChannel();
        }

        if (! $channel instanceof DelayableQueueChannel) {
            return;
        }

        $channel->releaseMessagesAwaitingFor($timeInMillisecondsOrDateTime);
    }
}
