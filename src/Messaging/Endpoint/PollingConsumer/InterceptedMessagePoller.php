<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\PollingConsumer;

use Ecotone\Api\ExtensionObject\PollingMetadata;
use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessagePoller;
use Ecotone\Messaging\Endpoint\ConsumerInterceptor;

/**
 * licence Apache-2.0
 */
final class InterceptedMessagePoller implements MessagePoller
{
    /**
     * @param ConsumerInterceptor[] $interceptors
     */
    public function __construct(private MessagePoller $messagePoller, private array $interceptors, private bool $drainsAQueue)
    {
    }

    public function receiveWithTimeout(PollingMetadata $pollingMetadata): ?Message
    {
        $message = $this->messagePoller->receiveWithTimeout($pollingMetadata);
        foreach ($this->interceptors as $consumerInterceptor) {
            $consumerInterceptor->postReceive($this->drainsAQueue && $message !== null);
        }

        return $message;
    }

    public function onConsumerStop(): void
    {
        $this->messagePoller->onConsumerStop();
    }
}
