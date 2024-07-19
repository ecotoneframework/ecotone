<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\DynamicChannel\SendingStrategy;

use Ecotone\Messaging\Channel\DynamicChannel\ChannelSendingStrategy;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * licence Enterprise
 */
final class CustomSendingStrategy implements ChannelSendingStrategy
{
    public function __construct(
        private MessagingEntrypoint $messagingEntrypoint,
        private string $channelNameToResolveSendingMessageChannel
    ) {
    }

    public function decideFor(Message $message): string
    {
        $channelName = $this->messagingEntrypoint->send(
            /** This need to be removed in order to return the Message correctly (order routing_slip, replyChannel) */
            MessageBuilder::fromMessage($message)
                ->removeHeader(MessageHeaders::ROUTING_SLIP),
            $this->channelNameToResolveSendingMessageChannel
        );
        Assert::notNull($channelName, "Channel name to send message to cannot be null. If you want to skip message sending, return 'nullChannel' instead.");

        return $channelName;
    }
}
