<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Endpoint;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Interface PollableFactory
 * @package SimplyCodedSoftware\IntegrationMessaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageHandlerConsumerBuilderFactory
{
    /**
     * @param ChannelResolver $channelResolver
     * @param MessageHandlerBuilder $messageHandlerBuilder
     * @return bool
     */
    public function isSupporting(ChannelResolver $channelResolver, MessageHandlerBuilder $messageHandlerBuilder) : bool;

    /**
     * @param ChannelResolver $channelResolver
     * @param ReferenceSearchService $referenceSearchService
     * @param MessageHandlerBuilder $messageHandlerBuilder
     * @return ConsumerLifecycle
     */
    public function create(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, MessageHandlerBuilder $messageHandlerBuilder) : ConsumerLifecycle;
}