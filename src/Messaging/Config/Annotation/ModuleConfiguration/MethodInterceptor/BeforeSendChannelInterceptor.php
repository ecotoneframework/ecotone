<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MethodInterceptor;

use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageChannel;
use Ecotone\Messaging\Channel\ChannelInterceptor;
use Ecotone\Messaging\Handler\MessageProcessor;
use Throwable;

/**
 * licence Apache-2.0
 */
class BeforeSendChannelInterceptor implements ChannelInterceptor
{
    public function __construct(private MessageProcessor $messageProcessor)
    {
    }

    /**
     * @inheritDoc
     */
    public function preSend(Message $message, MessageChannel $messageChannel): ?Message
    {
        return $this->messageProcessor->process($message);
    }

    /**
     * @inheritDoc
     */
    public function postSend(Message $message, MessageChannel $messageChannel): void
    {
    }

    /**
     * @inheritDoc
     */
    public function afterSendCompletion(Message $message, MessageChannel $messageChannel, ?Throwable $exception): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function preReceive(MessageChannel $messageChannel): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function postReceive(Message $message, MessageChannel $messageChannel): ?Message
    {
        return $message;
    }

    /**
     * @inheritDoc
     */
    public function afterReceiveCompletion(?Message $message, MessageChannel $messageChannel, ?Throwable $exception): void
    {
    }
}
