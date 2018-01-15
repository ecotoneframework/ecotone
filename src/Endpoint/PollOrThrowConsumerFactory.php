<?php

namespace SimplyCodedSoftware\Messaging\Endpoint;

use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\MessageHandler;
use SimplyCodedSoftware\Messaging\PollableChannel;

/**
 * Class PollOrThrowPollableFactory
 * @package SimplyCodedSoftware\Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PollOrThrowConsumerFactory implements ConsumerFactory
{
    /**
     * @inheritDoc
     */
    public function isSupporting(ChannelResolver $channelResolver, MessageHandlerBuilder $messageHandlerBuilder): bool
    {
        return $channelResolver->resolve($messageHandlerBuilder->getInputMessageChannelName()) instanceof PollableChannel;
    }

    /**
     * @inheritDoc
     */
    public function create(ChannelResolver $channelResolver, MessageHandlerBuilder $messageHandlerBuilder): ConsumerLifecycle
    {
        /** @var PollableChannel $pollableChannel */
        $pollableChannel = $channelResolver->resolve($messageHandlerBuilder->getInputMessageChannelName());

        return PollOrThrowExceptionConsumer::create($messageHandlerBuilder->getComponentName(), $pollableChannel, $messageHandlerBuilder->build());
    }
}