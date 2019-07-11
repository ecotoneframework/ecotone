<?php


namespace SimplyCodedSoftware\Amqp;

use SimplyCodedSoftware\Messaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerLifecycle;
use SimplyCodedSoftware\Messaging\Endpoint\MessageHandlerConsumerBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\PollingConsumer\PollingConsumerBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\PollingMetadata;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

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
    public function isSupporting(MessageHandlerBuilder $messageHandlerBuilder, MessageChannelBuilder $relatedMessageChannel): bool
    {
        return $relatedMessageChannel instanceof AmqpBackedMessageChannelBuilder;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, MessageHandlerBuilder $messageHandlerBuilder, PollingMetadata $pollingMetadata): ConsumerLifecycle
    {
        $pollingConsumerBuilder = new PollingConsumerBuilder();

        $pollingConsumerBuilder->addAroundInterceptor(AmqpAcknowledgeConfirmationInterceptor::createAroundInterceptor());

        return $pollingConsumerBuilder->build($channelResolver, $referenceSearchService, $messageHandlerBuilder, $pollingMetadata);
    }
}