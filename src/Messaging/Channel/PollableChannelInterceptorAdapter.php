<?php

namespace SimplyCodedSoftware\Messaging\Channel;

use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\PollableChannel;
use SimplyCodedSoftware\Messaging\Support\Assert;

/**
 * Class PollableChannelInterceptorAdapter
 * @package SimplyCodedSoftware\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PollableChannelInterceptorAdapter extends SendingInterceptorAdapter implements PollableChannel
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
        return $this->receiveFor(null);
    }

    /**
     * @inheritDoc
     */
    public function receiveWithTimeout(int $timeoutInMilliseconds): ?Message
    {
        return $this->receiveFor($timeoutInMilliseconds);
    }

    /**
     * @param int|null $timeout
     * @return Message|null
     */
    private function receiveFor(?int $timeout)
    {
        foreach ($this->sortedChannelInterceptors as $channelInterceptor) {
            if (!$channelInterceptor->preReceive($this->messageChannel)) {
                return null;
            }
        }

        try {
            if (is_null($timeout)) {
                $message = $this->messageChannel->receive();
            }else {
                $message = $this->messageChannel->receiveWithTimeout($timeout);
            }
        }catch (\Throwable $e) {
            foreach ($this->sortedChannelInterceptors as $channelInterceptor) {
                $channelInterceptor->afterReceiveCompletion(null, $this->messageChannel, $e);
            }

            throw $e;
        }

        if (is_null($message)) {
            return null;
        }

        foreach ($this->sortedChannelInterceptors as $channelInterceptor) {
            $channelInterceptor->postReceive($message, $this->messageChannel);
        }
        foreach ($this->sortedChannelInterceptors as $channelInterceptor) {
            $channelInterceptor->afterReceiveCompletion($message, $this->messageChannel, null);
        }

        return $message;
    }
}