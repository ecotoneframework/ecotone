<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Throwable;

/**
 * Interface ChannelInterceptor
 * @package Ecotone\Messaging\Channel
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface ChannelInterceptor
{
    /**
     * Invoked before the Message is actually sent to the channel.
     * Can return null to prevent sending message to channel
     *
     * @param Message $message
     * @param MessageChannel $messageChannel message channel that message will be send to
     * @return Message|null
     */
    public function preSend(Message $message, MessageChannel $messageChannel): ?Message;

    /**
     * Invoked immediately after success send invocation.
     *
     * @param Message $message
     * @param MessageChannel $messageChannel message channel that message was sent to
     */
    public function postSend(Message $message, MessageChannel $messageChannel): void;

    /**
     * Invoked after the completion of a send regardless of any exception that have been raised thus allowing for proper resource cleanup.
     * In case of exception, if true is returned, it means that exception was handled and should be skipped
     * Note that this will be invoked only if preSend did not return null.
     *
     * @param Message $message
     * @param MessageChannel $messageChannel
     * @param Throwable|null $exception
     *
     * @return bool In case of exception, return true to indicate that exception was handled and exception can be skipped
     */
    public function afterSendCompletion(Message $message, MessageChannel $messageChannel, ?Throwable $exception): bool;

    /**
     * Invoked as soon as receive is called and before a Message is actually retrieved.
     * can return false to prevent the receive operation from proceeding.
     *
     * Before receiving from subscription channel
     *
     * @param MessageChannel $messageChannel
     * @return bool
     */
    public function preReceive(MessageChannel $messageChannel): bool;

    /**
     * Invoked after the completion of a receive regardless of any exception that have been raised thus allowing for proper resource cleanup.
     * This will only called when preReceive return true
     * @param Message|null $message
     * @param MessageChannel $messageChannel
     * @param Throwable|null $exception
     * @return void
     */
    public function afterReceiveCompletion(?Message $message, MessageChannel $messageChannel, ?Throwable $exception): void;

    /**
     * Invoked immediately after a Message has been retrieved but before it is returned to the caller.
     *
     * @param Message $message message that was received
     * @param MessageChannel $messageChannel message channel that message was received from
     * @return Message|null
     */
    public function postReceive(Message $message, MessageChannel $messageChannel): ?Message;
}
