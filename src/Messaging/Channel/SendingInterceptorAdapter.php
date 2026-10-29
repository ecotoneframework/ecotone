<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\Support\Assert;
use Throwable;

/**
 * Class ChannelInterceptorAdapter
 * @package Ecotone\Messaging\Config
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 * @internal
 */
/**
 * licence Apache-2.0
 */
abstract class SendingInterceptorAdapter implements MessageChannelInterceptorAdapter
{
    /**
     * @var MessageChannel|MessageChannelInterceptorAdapter
     */
    protected $messageChannel;
    /**
     * @var ChannelInterceptor[]
     */
    protected array $sortedChannelInterceptors;

    /**
     * ChannelInterceptorAdapter constructor.
     * @param MessageChannel $messageChannel
     * @param ChannelInterceptor[] $sortedChannelInterceptors
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function __construct(MessageChannel $messageChannel, array $sortedChannelInterceptors)
    {
        Assert::allInstanceOfType($sortedChannelInterceptors, ChannelInterceptor::class);
        $this->sortedChannelInterceptors = $sortedChannelInterceptors;

        $this->initialize($messageChannel);
    }

    /**
     * @inheritDoc
     */
    public function send(Message $message): void
    {
        $messageToSend = $message;
        $executedInterceptors = [];
        $isMessageDropped = false;
        try {
            foreach ($this->sortedChannelInterceptors as $channelInterceptor) {
                $transformedMessage = $channelInterceptor->preSend($messageToSend, $this->messageChannel);

                if ($transformedMessage === null) {
                    $isMessageDropped = true;

                    break;
                }

                $messageToSend = $transformedMessage;
                $executedInterceptors[] = $channelInterceptor;
            }

            if (! $isMessageDropped) {
                $this->messageChannel->send($messageToSend);
            }
        } catch (Throwable $exception) {
            $shouldThrow = true;
            $firstCleanupFailure = null;
            foreach ($executedInterceptors as $channelInterceptor) {
                try {
                    if ($channelInterceptor->afterSendCompletion($messageToSend, $this->messageChannel, $exception)) {
                        $shouldThrow = false;
                    }
                } catch (Throwable $cleanupFailure) {
                    $firstCleanupFailure ??= $cleanupFailure;
                }
            }

            if ($shouldThrow) {
                throw $exception;
            }

            $this->executePostSend($messageToSend, $executedInterceptors, $firstCleanupFailure);

            return;
        }

        $firstCleanupFailure = null;
        foreach ($executedInterceptors as $channelInterceptor) {
            try {
                $channelInterceptor->afterSendCompletion($messageToSend, $this->messageChannel, null);
            } catch (Throwable $cleanupFailure) {
                $firstCleanupFailure ??= $cleanupFailure;
            }
        }

        $this->executePostSend($messageToSend, $executedInterceptors, $firstCleanupFailure);
    }

    /**
     * @param ChannelInterceptor[] $executedInterceptors
     */
    private function executePostSend(Message $messageToSend, array $executedInterceptors, ?Throwable $firstCleanupFailure): void
    {
        foreach ($executedInterceptors as $channelInterceptor) {
            try {
                $channelInterceptor->postSend($messageToSend, $this->messageChannel);
            } catch (Throwable $cleanupFailure) {
                $firstCleanupFailure ??= $cleanupFailure;
            }
        }

        if ($firstCleanupFailure !== null) {
            throw $firstCleanupFailure;
        }
    }

    /**
     * @inheritDoc
     */
    public function getInternalMessageChannel(): MessageChannel
    {
        if ($this->messageChannel instanceof MessageChannelInterceptorAdapter) {
            return $this->messageChannel->getInternalMessageChannel();
        }

        return $this->messageChannel;
    }

    /**
     * @param MessageChannel $messageChannel
     */
    protected function initialize(MessageChannel $messageChannel): void
    {
        $this->messageChannel = $messageChannel;
    }
}
