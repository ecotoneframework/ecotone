<?php

namespace SimplyCodedSoftware\Messaging\Config;

use SimplyCodedSoftware\Messaging\Channel\ChannelInterceptorAdapter;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\PollableChannel;
use SimplyCodedSoftware\Messaging\Support\Assert;

/**
 * Class PollableChannelInterceptorAdapter
 * @package SimplyCodedSoftware\Messaging\Config
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