<?php


namespace SimplyCodedSoftware\Messaging\Endpoint\EventDriven;


use SimplyCodedSoftware\Messaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerLifecycle;
use SimplyCodedSoftware\Messaging\Endpoint\MessageHandlerConsumerBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\PollingMetadata;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\SubscribableChannel;

class LazyEventDrivenConsumerBuilder implements MessageHandlerConsumerBuilder
{
    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, MessageHandlerBuilder $messageHandlerBuilder, PollingMetadata $pollingMetadata): ConsumerLifecycle
    {
        /** @var SubscribableChannel $subscribableChannel */
        $subscribableChannel = $channelResolver->resolve($messageHandlerBuilder->getInputMessageChannelName());

        return new EventDrivenConsumer(
            $messageHandlerBuilder->getEndpointId(),
            $subscribableChannel,
            new LazyMessageHandler($messageHandlerBuilder, $channelResolver, $referenceSearchService)
        );
    }

    /**
     * @inheritDoc
     */
    public function isSupporting(MessageHandlerBuilder $messageHandlerBuilder, MessageChannelBuilder $relatedMessageChannel): bool
    {
        return $relatedMessageChannel instanceof SimpleMessageChannelBuilder && !$relatedMessageChannel->isPollable();
    }
}