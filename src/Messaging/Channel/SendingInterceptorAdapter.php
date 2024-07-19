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
        foreach ($this->sortedChannelInterceptors as $channelInterceptor) {
            $messageToSend = $channelInterceptor->preSend($messageToSend, $this->messageChannel);

            if ($messageToSend === null) {
                return;
            }
        }

        $exception = null;
        try {
            $this->messageChannel->send($messageToSend);
        } catch (Throwable $exception) {
        } finally {
            $shouldThrow = $exception !== null;
            foreach ($this->sortedChannelInterceptors as $channelInterceptor) {
                if ($channelInterceptor->afterSendCompletion($messageToSend, $this->messageChannel, $exception)) {
                    $shouldThrow = false;
                }
            }

            if ($shouldThrow) {
                throw $exception;
            }
        }
        foreach ($this->sortedChannelInterceptors as $channelInterceptor) {
            $channelInterceptor->postSend($messageToSend, $this->messageChannel);
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
