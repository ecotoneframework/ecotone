<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config;

use SimplyCodedSoftware\IntegrationMessaging\Channel\ChannelInterceptorAdapter;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageChannel;
use SimplyCodedSoftware\IntegrationMessaging\PollableChannel;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;

/**
 * Class PollableChannelInterceptorAdapter
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class PollableChannelInterceptorAdapter extends ChannelInterceptorAdapter implements PollableChannel
{
    /**
     * @var PollableChannel
     */
    protected $messageChannel;

    /**
     * @inheritDoc
     */
    protected function initialize(MessageChannel $messageChannel): void
    {
        Assert::isSubclassOf($messageChannel, PollableChannel::class, "Pollable interceptor expects pollable channel");

        $this->messageChannel = $messageChannel;
    }

    /**
     * @inheritDoc
     */
    public function receive(): ?Message
    {
        foreach ($this->sortedChannelInterceptors as $channelInterceptor) {
            $channelInterceptor->preReceive($this->messageChannel);
        }

        $message = $this->messageChannel->receive();

        foreach ($this->sortedChannelInterceptors as $channelInterceptor) {
            $channelInterceptor->postReceive($message, $this->messageChannel);
        }

        return $message;
    }
}