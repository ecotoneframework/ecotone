<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\DynamicChannel\ReceivingStrategy;

use Ecotone\Messaging\Channel\DynamicChannel\ChannelReceivingStrategy;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\NullableMessageChannel;
use Ecotone\Messaging\Support\Assert;

/**
 * licence Enterprise
 */
final class SkippingReceivingStrategy implements ChannelReceivingStrategy
{
    public function __construct(
        private MessagingEntrypoint         $messagingEntrypoint,
        private string                      $dynamicChannelName,
        private RoundRobinReceivingStrategy $robinReceivingStrategy,
        private string $channelNameToDecideOnTheConsumption
    ) {
    }

    public function decide(): string
    {
        $decidedChannel = $this->robinReceivingStrategy->decide();

        $shouldPoll = $this->messagingEntrypoint->sendWithHeaders(
            $decidedChannel,
            [
                'dynamicChannelName' => $this->dynamicChannelName,
            ],
            $this->channelNameToDecideOnTheConsumption
        );
        Assert::isTrue(is_bool($shouldPoll), 'Result decision should be boolean');

        if ($shouldPoll) {
            return $decidedChannel;
        }

        return NullableMessageChannel::CHANNEL_NAME;
    }
}
