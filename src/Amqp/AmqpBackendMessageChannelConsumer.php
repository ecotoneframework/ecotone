<?php


namespace SimplyCodedSoftware\Amqp;

use SimplyCodedSoftware\Messaging\Channel\MessageChannelInterceptorAdapter;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerLifecycle;
use SimplyCodedSoftware\Messaging\Endpoint\MessageHandlerConsumerBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\PollingMetadata;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\PollableChannel;

/**
 * Class AmqpBackendMessageChannelConsumer
 * @package SimplyCodedSoftware\Amqp
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpBackendMessageChannelConsumer implements MessageHandlerConsumerBuilder
{
    /**
     * @inheritDoc
     */
    public function isSupporting(ChannelResolver $channelResolver, MessageHandlerBuilder $messageHandlerBuilder): bool
    {
        $messageChannel = $channelResolver->resolve($messageHandlerBuilder->getInputMessageChannelName());
        if ($messageChannel instanceof MessageChannelInterceptorAdapter) {
            return $messageChannel->getInternalMessageChannel() instanceof AmqpBackendMessageChannel;
        }


        return $messageChannel instanceof AmqpBackendMessageChannel;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, MessageHandlerBuilder $messageHandlerBuilder, PollingMetadata $pollingMetadata): ConsumerLifecycle
    {

    }
}