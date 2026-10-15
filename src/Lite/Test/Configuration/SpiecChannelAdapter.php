<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test\Configuration;

use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageChannel;
use Ecotone\Messaging\Channel\ChannelInterceptor;
use Throwable;

/**
 * licence Apache-2.0
 */
final class SpiecChannelAdapter implements ChannelInterceptor
{
    public function __construct(private string $channelName, private MessageCollectorHandler $messageCollectorHandler)
    {
    }

    public function preSend(Message $message, MessageChannel $messageChannel): ?Message
    {
        return $message;
    }

    public function postSend(Message $message, MessageChannel $messageChannel): void
    {
        $this->messageCollectorHandler->recordSpiedChannelMessage($this->channelName, $message);
    }

    public function afterSendCompletion(Message $message, MessageChannel $messageChannel, ?Throwable $exception): bool
    {
        return false;
    }

    public function preReceive(MessageChannel $messageChannel): bool
    {
        return true;
    }

    public function postReceive(Message $message, MessageChannel $messageChannel): ?Message
    {
        return $message;
    }

    public function afterReceiveCompletion(?Message $message, MessageChannel $messageChannel, ?Throwable $exception): void
    {
    }
}
