<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config;

use SimplyCodedSoftware\IntegrationMessaging\MessageChannel;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\SubscribableChannel;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;

/**
 * Class EventDrivenChannelInterceptorAdapter
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class EventDrivenChannelInterceptorAdapter extends ChannelInterceptorAdapter implements SubscribableChannel
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
        Assert::isSubclassOf($messageChannel, SubscribableChannel::class, "Event driven interceptor expects subscribable channel");

        $this->messageChannel = $messageChannel;
    }
}