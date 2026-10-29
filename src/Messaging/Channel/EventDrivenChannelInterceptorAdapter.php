<?php

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\SubscribableChannel;
use Ecotone\Messaging\Support\Assert;

/**
 * Class EventDrivenChannelInterceptorAdapter
 * @package Ecotone\Messaging\Config
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class EventDrivenChannelInterceptorAdapter extends SendingInterceptorAdapter implements SubscribableChannel
{
    /**
     * @var SubscribableChannel
     */
    protected $messageChannel;

    /**
     * @inheritDoc
     */
    public function subscribe(MessageHandler $messageHandler): void
    {
        $this->messageChannel->subscribe($messageHandler);
    }

    /**
     * @inheritDoc
     */
    public function unsubscribe(MessageHandler $messageHandler): void
    {
        $this->messageChannel->unsubscribe($messageHandler);
    }

    /**
     * @inheritDoc
     */
    protected function initialize(MessageChannel $messageChannel): void
    {
        Assert::isSubclassOf($messageChannel, SubscribableChannel::class, 'Event driven interceptor expects subscribable channel');

        $this->messageChannel = $messageChannel;
    }
}
