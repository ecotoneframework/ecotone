<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test\Configuration;

use DateTimeInterface;
use Ecotone\Messaging\Channel\DelayableQueueChannel;
use Ecotone\Messaging\Channel\MessageChannelInterceptorAdapter;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Scheduling\TimeSpan;
use Ecotone\Messaging\Support\Assert;

/**
 * licence Apache-2.0
 */
final class DelayedMessageReleaseHandler
{
    public function releaseMessagesAwaitingFor(string $channelName, int|TimeSpan|DateTimeInterface $timeInMillisecondsOrDateTime, ChannelResolver $channelResolver): void
    {
        /** @var DelayableQueueChannel|MessageChannelInterceptorAdapter $channel */
        $channel = $channelResolver->resolve($channelName);
        if ($channel instanceof MessageChannelInterceptorAdapter) {
            $channel = $channel->getInternalMessageChannel();
        }

        Assert::isTrue($channel instanceof DelayableQueueChannel, sprintf('Used %s channel to release delayed message, use instead of %s.', $channel::class, DelayableQueueChannel::class));

        $channel->releaseMessagesAwaitingFor($timeInMillisecondsOrDateTime);
    }
}
