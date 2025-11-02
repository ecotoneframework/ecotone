<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\DynamicChannel;

use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Support\Assert;

/**
 * licence Enterprise
 */
final class DynamicMessageChannel implements PollableChannel
{
    /**
     * @param string[] $channelNamesToReceive
     * @param int $currentChannelIndexToSend
     */
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
        $message = $channel->receiveWithTimeout($pollingMetadata);
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
}
