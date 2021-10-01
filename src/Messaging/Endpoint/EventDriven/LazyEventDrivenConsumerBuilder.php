<?php


namespace Ecotone\Messaging\Endpoint\EventDriven;


use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Endpoint\ConsumerLifecycle;
use Ecotone\Messaging\Endpoint\MessageHandlerConsumerBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\SubscribableChannel;

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

    public function isPollingConsumer(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function isSupporting(MessageHandlerBuilder $messageHandlerBuilder, MessageChannelBuilder $relatedMessageChannel): bool
    {
        return $relatedMessageChannel instanceof SimpleMessageChannelBuilder && !$relatedMessageChannel->isPollable();
    }
}