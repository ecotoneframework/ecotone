<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\Support\Assert;

/**
 * Class ChannelInterceptorAdapter
 * @package Ecotone\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
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
        }

        if (!$messageToSend) {
            return;
        }

        try {
            $this->messageChannel->send($messageToSend);
        }catch (\Throwable $e) {
            foreach ($this->sortedChannelInterceptors as $channelInterceptor) {
                $channelInterceptor->afterSendCompletion($messageToSend, $this->messageChannel, $e);
            }

            throw $e;
        }
        foreach ($this->sortedChannelInterceptors as $channelInterceptor) {
            $channelInterceptor->postSend($messageToSend, $this->messageChannel);
        }
        foreach ($this->sortedChannelInterceptors as $channelInterceptor) {
            $channelInterceptor->afterSendCompletion($messageToSend, $this->messageChannel, null);
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
    protected function initialize(MessageChannel $messageChannel) : void
    {
        $this->messageChannel = $messageChannel;
    }
}