<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Channel;

use Ecotone\Messaging\Channel\ChannelInterceptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Throwable;

/**
 * Test implementation of ChannelInterceptor for PHPUnit 10 compatibility
 */
class TestChannelInterceptor implements ChannelInterceptor
{
    private ?Message $returnMessageOnPreSend = null;
    private bool $returnValueOnPreReceive = true;
    private bool $returnValueOnAfterSendCompletion = false;
    private ?Message $returnMessageOnPostReceive = null;
    private bool $returnNullOnPreSend = false;

    private ?Message $capturedMessage = null;
    private ?MessageChannel $capturedChannel = null;
    private ?Throwable $capturedException = null;

    private bool $preSendCalled = false;
    private bool $postSendCalled = false;
    private bool $afterSendCompletionCalled = false;
    private bool $preReceiveCalled = false;
    private bool $afterReceiveCompletionCalled = false;
    private bool $postReceiveCalled = false;

    public function __construct(?Message $returnMessageOnPreSend = null, bool $returnValueOnPreReceive = true, bool $returnValueOnAfterSendCompletion = false, ?Message $returnMessageOnPostReceive = null)
    {
        $this->returnMessageOnPreSend = $returnMessageOnPreSend;
        $this->returnValueOnPreReceive = $returnValueOnPreReceive;
        $this->returnValueOnAfterSendCompletion = $returnValueOnAfterSendCompletion;
        $this->returnMessageOnPostReceive = $returnMessageOnPostReceive;
    }

    public function preSend(Message $message, MessageChannel $messageChannel): ?Message
    {
        $this->preSendCalled = true;
        $this->capturedMessage = $message;
        $this->capturedChannel = $messageChannel;
        if ($this->returnNullOnPreSend) {
            return null;
        }
        return $this->returnMessageOnPreSend ?? $message;
    }

    public function setReturnNullOnPreSend(bool $returnNullOnPreSend): void
    {
        $this->returnNullOnPreSend = $returnNullOnPreSend;
    }

    public function setReturnMessageOnPreSend(?Message $message): void
    {
        $this->returnMessageOnPreSend = $message;
    }

    public function postSend(Message $message, MessageChannel $messageChannel): void
    {
        $this->postSendCalled = true;
        $this->capturedMessage = $message;
        $this->capturedChannel = $messageChannel;
    }

    public function afterSendCompletion(Message $message, MessageChannel $messageChannel, ?Throwable $exception): bool
    {
        $this->afterSendCompletionCalled = true;
        $this->capturedMessage = $message;
        $this->capturedChannel = $messageChannel;
        $this->capturedException = $exception;
        return $this->returnValueOnAfterSendCompletion;
    }

    public function preReceive(MessageChannel $messageChannel): bool
    {
        $this->preReceiveCalled = true;
        $this->capturedChannel = $messageChannel;
        return $this->returnValueOnPreReceive;
    }

    public function afterReceiveCompletion(?Message $message, MessageChannel $messageChannel, ?Throwable $exception): void
    {
        $this->afterReceiveCompletionCalled = true;
        $this->capturedMessage = $message;
        $this->capturedChannel = $messageChannel;
        $this->capturedException = $exception;
    }

    public function postReceive(Message $message, MessageChannel $messageChannel): ?Message
    {
        $this->postReceiveCalled = true;
        $this->capturedMessage = $message;
        $this->capturedChannel = $messageChannel;
        return $this->returnMessageOnPostReceive ?? $message;
    }

    public function wasPreSendCalled(): bool
    {
        return $this->preSendCalled;
    }

    public function wasPostSendCalled(): bool
    {
        return $this->postSendCalled;
    }

    public function wasAfterSendCompletionCalled(): bool
    {
        return $this->afterSendCompletionCalled;
    }

    public function wasPreReceiveCalled(): bool
    {
        return $this->preReceiveCalled;
    }

    public function wasAfterReceiveCompletionCalled(): bool
    {
        return $this->afterReceiveCompletionCalled;
    }

    public function wasPostReceiveCalled(): bool
    {
        return $this->postReceiveCalled;
    }

    public function getCapturedMessage(): ?Message
    {
        return $this->capturedMessage;
    }

    public function getCapturedChannel(): ?MessageChannel
    {
        return $this->capturedChannel;
    }

    public function getCapturedException(): ?Throwable
    {
        return $this->capturedException;
    }
}
