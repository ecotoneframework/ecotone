<?php

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Support\Assert;
use Throwable;

/**
 * Class PollableChannelInterceptorAdapter
 * @package Ecotone\Messaging\Config
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
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
        Assert::isSubclassOf($messageChannel, PollableChannel::class, 'Pollable interceptor expects pollable channel');

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
    public function receiveWithTimeout(PollingMetadata $pollingMetadata): ?Message
    {
        return $this->receiveFor($pollingMetadata);
    }

    public function onConsumerStop(): void
    {
        $this->messageChannel->onConsumerStop();
    }

    /**
     * @param PollingMetadata|null $pollingMetadata
     */
    private function receiveFor(?PollingMetadata $pollingMetadata): ?Message
    {
        foreach ($this->sortedChannelInterceptors as $channelInterceptor) {
            if (! $channelInterceptor->preReceive($this->messageChannel)) {
                return null;
            }
        }

        try {
            if (is_null($pollingMetadata)) {
                $message = $this->messageChannel->receive();
            } else {
                $message = $this->messageChannel->receiveWithTimeout($pollingMetadata);
            }
        } catch (Throwable $e) {
            foreach ($this->sortedChannelInterceptors as $channelInterceptor) {
                $channelInterceptor->afterReceiveCompletion(null, $this->messageChannel, $e);
            }

            throw $e;
        }

        foreach ($this->sortedChannelInterceptors as $channelInterceptor) {
            $channelInterceptor->afterReceiveCompletion($message, $this->messageChannel, null);
        }

        if (is_null($message)) {
            return null;
        }

        foreach ($this->sortedChannelInterceptors as $channelInterceptor) {
            $channelInterceptor->postReceive($message, $this->messageChannel);
        }

        return $message;
    }

    public function __toString()
    {
        return (string)$this->messageChannel;
    }
}
