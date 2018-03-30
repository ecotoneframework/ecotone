<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Endpoint;

use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\IntegrationMessaging\Channel\MessageChannelAdapter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\SubscribableChannel;

/**
 * Class EventDrivenConsumerFactory
 * @package SimplyCodedSoftware\IntegrationMessaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EventDrivenMessageHandlerConsumerBuilderFactory implements MessageHandlerConsumerBuilderFactory
{
    /**
     * @inheritDoc
     */
    public function isSupporting(ChannelResolver $channelResolver, MessageHandlerBuilder $messageHandlerBuilder): bool
    {
        $messageChannel = $channelResolver->resolve($messageHandlerBuilder->getInputMessageChannelName());

        if ($messageChannel instanceof MessageChannelAdapter) {
            return $messageChannel->getInternalMessageChannel() instanceof SubscribableChannel;
        }

        return $messageChannel instanceof SubscribableChannel;
    }

    /**
     * @inheritDoc
     */
    public function create(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, MessageHandlerBuilder $messageHandlerBuilder): ConsumerLifecycle
    {
        /** @var SubscribableChannel $subscribableChannel */
        $subscribableChannel = $channelResolver->resolve($messageHandlerBuilder->getInputMessageChannelName());

        return new EventDrivenConsumer(
            get_class($messageHandlerBuilder) . Uuid::uuid4()->toString(),
            $subscribableChannel,
            $messageHandlerBuilder->build($channelResolver, $referenceSearchService)
        );
    }
}