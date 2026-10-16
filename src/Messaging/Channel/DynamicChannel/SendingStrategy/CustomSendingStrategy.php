<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\DynamicChannel\SendingStrategy;

use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Messaging\Channel\DynamicChannel\ChannelSendingStrategy;
use Ecotone\Messaging\Gateway\MessagingEntrypointService;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 */
final class CustomSendingStrategy implements ChannelSendingStrategy
{
    public function __construct(
        private MessagingEntrypointService $messagingEntrypoint,
        private string $channelNameToResolveSendingMessageChannel,
        private MessageBuilder $messageBuilder,
    ) {
    }

    public function decideFor(Message $message): string
    {
        $channelName = $this->messagingEntrypoint->send(
            /** This need to be removed in order to return the Message correctly (order routing_slip, replyChannel) */
            $this->messageBuilder->fromMessage($message)
                ->removeHeader(MessageHeaders::ROUTING_SLIP),
            $this->channelNameToResolveSendingMessageChannel
        );
        Assert::notNull($channelName, "Channel name to send message to cannot be null. If you want to skip message sending, return 'nullChannel' instead.");

        return $channelName;
    }
}
