<?php

declare(strict_types=1);

namespace Ecotone\Test;

use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Messaging\ConfiguredMessagingSystem;
use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageChannel;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Messaging\PollableChannel;
use Ecotone\Messaging\Gateway\MessagingEntrypointService;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * licence Apache-2.0
 * @internal Ecotone's own channel and interceptor suites drive a channel through this; an application test sends a command or event and runs the channel
 */
final class ChannelTestSupport
{
    private function __construct(private FlowTestSupport $ecotone)
    {
    }

    public static function of(FlowTestSupport $ecotone): self
    {
        return new self($ecotone);
    }

    public function getMessageChannel(string $channelName): MessageChannel|PollableChannel
    {
        return $this->ecotone->getServiceFromContainer(ConfiguredMessagingSystem::class)->getMessageChannelByName($channelName);
    }

    public function receiveMessageFrom(string $channelName): ?Message
    {
        $messageChannel = $this->getMessageChannel($channelName);
        if (! $messageChannel instanceof PollableChannel) {
            throw new InvalidArgumentException("Channel {$channelName} is not pollable, so nothing can be received from it. Receive from a channel registered as a queue, or assert on the handler the channel delivers to.");
        }

        return $messageChannel->receive();
    }

    public function sendMessageDirectToChannel(string $targetChannel, Message $message): mixed
    {
        Assert::isFalse($message->getHeaders()->containsKey(MessagingEntrypointService::ENTRYPOINT), 'Message must not contain entrypoint header. Make use of first argument in sendDirectToChannel method');

        return $this->entrypoint()->sendMessage(
            $this->ecotone->getServiceFromContainer(MessageBuilder::class)->fromMessage($message)
                ->setHeader(MessagingEntrypointService::ENTRYPOINT, $targetChannel)
                ->build()
        );
    }

    public function sendMessageDirectToChannelWithMessageReply(string $targetChannel, Message $message): Message
    {
        Assert::isFalse($message->getHeaders()->containsKey(MessagingEntrypointService::ENTRYPOINT), 'Message must not contain entrypoint header. Make use of first argument in sendDirectToChannel method');

        return $this->entrypoint()->sendWithHeadersWithMessageReply(
            $message->getPayload(),
            $message->getHeaders()->headers(),
            $targetChannel,
            $message->getHeaders()->containsKey(MessageHeaders::ROUTING_SLIP) ? $message->getHeaders()->get(MessageHeaders::ROUTING_SLIP) : null
        );
    }

    private function entrypoint(): MessagingEntrypointService
    {
        return $this->ecotone->getServiceFromContainer(MessagingEntrypointService::class);
    }
}
