<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Endpoint\PollOrThrow;

use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\IntegrationMessaging\Channel\MessageChannelAdapter;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\ConsumerLifecycle;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\MessageHandlerConsumerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\PollingMetadata;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\PollableChannel;

/**
 * Class PollOrThrowPollableFactory
 * @package SimplyCodedSoftware\IntegrationMessaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PollOrThrowMessageHandlerConsumerBuilder implements MessageHandlerConsumerBuilder
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
    public function create(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, MessageHandlerBuilder $messageHandlerBuilder, ?PollingMetadata $pollingMetadata): ConsumerLifecycle
    {
        /** @var PollableChannel $pollableChannel */
        $pollableChannel = $channelResolver->resolve($messageHandlerBuilder->getInputMessageChannelName());

        return PollOrThrowExceptionConsumer::create(get_class($messageHandlerBuilder) . Uuid::uuid4()->toString(), $pollableChannel, $messageHandlerBuilder->build(
            $channelResolver, $referenceSearchService
        ));
    }
}