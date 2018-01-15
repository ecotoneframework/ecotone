<?php

namespace SimplyCodedSoftware\Messaging\Endpoint;

use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\SubscribableChannel;

/**
 * Class EventDrivenConsumerFactory
 * @package SimplyCodedSoftware\Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EventDrivenConsumerFactory implements ConsumerFactory
{
    /**
     * @inheritDoc
     */
    public function isSupporting(ChannelResolver $channelResolver, MessageHandlerBuilder $messageHandlerBuilder): bool
    {
        return $channelResolver->resolve($messageHandlerBuilder->getInputMessageChannelName()) instanceof SubscribableChannel;
    }

    /**
     * @inheritDoc
     */
    public function create(ChannelResolver $channelResolver, MessageHandlerBuilder $messageHandlerBuilder): ConsumerLifecycle
    {
        /** @var SubscribableChannel $subscribableChannel */
        $subscribableChannel = $channelResolver->resolve($messageHandlerBuilder->getInputMessageChannelName());

        return new EventDrivenConsumer(
            $messageHandlerBuilder->getComponentName(),
            $subscribableChannel,
            $messageHandlerBuilder->build()
        );
    }
}