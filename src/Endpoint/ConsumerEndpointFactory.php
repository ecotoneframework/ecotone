<?php

namespace Messaging\Endpoint;

use Messaging\Handler\ChannelResolver;
use Messaging\Handler\MessageHandlerBuilder;
use Messaging\MessageChannel;
use Messaging\MessageHandler;
use Messaging\SubscribableChannel;

/**
 * Class ConsumerEndpointFactory - Responsible for creating consumers from builders
 * @package Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ConsumerEndpointFactory
{
    /**
     * @var ChannelResolver
     */
    private $channelResolver;

    /**
     * ConsumerEndpointFactory constructor.
     * @param ChannelResolver $channelResolver
     */
    public function __construct(ChannelResolver $channelResolver)
    {
        $this->channelResolver = $channelResolver;
    }

    /**
     * @param MessageHandlerBuilder $messageHandlerBuilder
     * @return ConsumerLifecycle
     */
    public function create(MessageHandlerBuilder $messageHandlerBuilder) : ConsumerLifecycle
    {
        $messageChannel = $messageHandlerBuilder->getInputMessageChannel();
        $messageHandlerBuilder = $messageHandlerBuilder->setChannelResolver($this->channelResolver);

        if ($messageChannel instanceof SubscribableChannel) {
            return new EventDrivenConsumer($messageHandlerBuilder->messageHandlerName(), $messageChannel, $messageHandlerBuilder->build());
        }
    }
}