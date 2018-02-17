<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config;

use SimplyCodedSoftware\IntegrationMessaging\Channel\ChannelInterceptor;
use SimplyCodedSoftware\IntegrationMessaging\Channel\MessageChannelAdapter;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageChannel;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;

/**
 * Class ChannelInterceptorAdapter
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
abstract class ChannelInterceptorAdapter implements MessageChannelAdapter
{
    /**
     * @var MessageChannel
     */
    protected $messageChannel;
    /**
     * @var ChannelInterceptor
     */
    protected $sortedChannelInterceptors;

    /**
     * ChannelInterceptorAdapter constructor.
     * @param MessageChannel $messageChannel
     * @param ChannelInterceptor[] $sortedChannelInterceptors
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
                $channelInterceptor->postSend($messageToSend, $this->messageChannel, false);
            }

            throw $e;
        }

        foreach ($this->sortedChannelInterceptors as $channelInterceptor) {
            $channelInterceptor->postSend($messageToSend, $this->messageChannel, true);
        }
    }

    /**
     * @inheritDoc
     */
    public function getInternalMessageChannel(): MessageChannel
    {
        if ($this->messageChannel instanceof MessageChannelAdapter) {
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