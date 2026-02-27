<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\DynamicChannel\ReceivingStrategy;

use Ecotone\Messaging\Channel\DynamicChannel\ChannelReceivingStrategy;
use Ecotone\Messaging\Gateway\MessagingEntrypointService;
use Ecotone\Messaging\Support\Assert;

/**
 * licence Enterprise
 */
final class CustomReceivingStrategy implements ChannelReceivingStrategy
{
    public function __construct(
        private MessagingEntrypointService $messagingEntrypoint,
        private string $channelNameToResolveReceivingMessageChannel
    ) {
    }

    public function decide(): string
    {
        $channelToPoll = $this->messagingEntrypoint->send([], $this->channelNameToResolveReceivingMessageChannel);
        Assert::notNull($channelToPoll, "Channel name to poll message from cannot be null. If you want to skip message receiving, return 'nullChannel' instead.");

        return $channelToPoll;
    }
}
