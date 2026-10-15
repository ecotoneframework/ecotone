<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\DynamicChannel;

use Ecotone\Api\ExtensionObject\PollingMetadata;
use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageChannel;
use Ecotone\Api\Messaging\PollableChannel;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Support\Assert;

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
final class DynamicMessageChannel implements PollableChannel
{
    public function __construct(
        private string                  $channelName,
        private InternalChannelResolver $channelResolver,
        private ChannelSendingStrategy  $sendingStrategy,
        private ChannelReceivingStrategy $receivingStrategy,
        private LoggingGateway          $loggingGateway,
    ) {
    }

    public function send(Message $message): void
    {
        $channelName = $this->sendingStrategy->decideFor($message);
        Assert::notNullAndEmpty($channelName, "Channel name to send message to cannot be null. If you want to skip message sending, return 'nullChannel' instead.");

        $channel = $this->channelResolver->resolve($channelName);
        $this->loggingGateway->info("Decided to send message to `{$channelName}` for `{$this->channelName}`", $message, ['channel_name' => $this->channelName, 'chosen_channel_name' => $channelName]);

        $channel->send($message);
    }

    public function receiveWithTimeout(PollingMetadata $pollingMetadata): ?Message
    {
        $channelName = $this->receivingStrategy->decide();
        Assert::notNullAndEmpty($channelName, "Channel name to poll message from cannot be null. If you want to skip message receiving, return 'nullChannel' instead.");

        $channel = $this->resolveMessageChannel($channelName);
        $message = $channel->receiveWithTimeout(
            $pollingMetadata->withPolledChannelName($channelName)
        );
        $this->loggingGateway->info("Decided to received message from `{$channelName}` for `{$this->channelName}`", $message, ['channel_name' => $this->channelName, 'chosen_channel_name' => $channelName]);

        return $message;
    }

    public function receive(): ?Message
    {
        $channelName = $this->receivingStrategy->decide();
        Assert::notNullAndEmpty($channelName, "Channel name to poll message from cannot be null. If you want to skip message receiving, return 'nullChannel' instead.");

        $channel = $this->resolveMessageChannel($channelName);

        $message = $channel->receive();
        $this->loggingGateway->info("Decided to received message from `{$channelName}` for `{$this->channelName}`", $message, ['channel_name' => $this->channelName, 'chosen_channel_name' => $channelName]);

        return $message;
    }

    public function onConsumerStop(): void
    {
        // No cleanup needed for dynamic channels
    }

    private function resolveMessageChannel(string $channelName): MessageChannel
    {
        return $this->channelResolver->resolve($channelName);
    }

    public function __toString(): string
    {
        return 'dynamic: ' . $this->channelName;
    }
}
