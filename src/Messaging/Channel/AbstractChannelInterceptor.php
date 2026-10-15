<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel;

use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageChannel;
use Throwable;

/**
 * Class AbstractChannelInterceptor
 * @package Ecotone\Messaging\Channel
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
abstract class AbstractChannelInterceptor implements ChannelInterceptor
{
    /**
     * @inheritDoc
     */
    public function preSend(Message $message, MessageChannel $messageChannel): ?Message
    {
        return $message;
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
