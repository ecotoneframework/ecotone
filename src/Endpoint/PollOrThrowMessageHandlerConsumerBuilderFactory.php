<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Endpoint;

use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\IntegrationMessaging\Channel\MessageChannelAdapter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\PollableChannel;

/**
 * Class PollOrThrowPollableFactory
 * @package SimplyCodedSoftware\IntegrationMessaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PollOrThrowMessageHandlerConsumerBuilderFactory implements MessageHandlerConsumerBuilderFactory
{
    /**
     * @inheritDoc
     */
    public function isSupporting(ChannelResolver $channelResolver, MessageHandlerBuilder $messageHandlerBuilder): bool
    {
        $messageChannel = $channelResolver->resolve($messageHandlerBuilder->getInputMessageChannelName());

        if ($messageChannel instanceof MessageChannelAdapter) {
            return $messageChannel->getInternalMessageChannel() instanceof PollableChannel;
        }

        return $messageChannel instanceof PollableChannel;
    }

    /**
     * @inheritDoc
     */
    public function create(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, MessageHandlerBuilder $messageHandlerBuilder): ConsumerLifecycle
    {
        /** @var PollableChannel $pollableChannel */
        $pollableChannel = $channelResolver->resolve($messageHandlerBuilder->getInputMessageChannelName());

        return PollOrThrowExceptionConsumer::create(get_class($messageHandlerBuilder) . Uuid::uuid4()->toString(), $pollableChannel, $messageHandlerBuilder->build(
            $channelResolver, $referenceSearchService
        ));
    }
}