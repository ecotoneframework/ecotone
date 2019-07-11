<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Endpoint;

use SimplyCodedSoftware\Messaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Interface PollableFactory
 * @package SimplyCodedSoftware\Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageHandlerConsumerBuilder
{
    /**
     * @inheritDoc
     */
    public function isSupporting(MessageHandlerBuilder $messageHandlerBuilder, MessageChannelBuilder $relatedMessageChannel): bool;

    /**
     * @param ChannelResolver $channelResolver
     * @param ReferenceSearchService $referenceSearchService
     * @param MessageHandlerBuilder $messageHandlerBuilder
     * @param PollingMetadata $pollingMetadata
     * @return ConsumerLifecycle
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, MessageHandlerBuilder $messageHandlerBuilder, PollingMetadata $pollingMetadata): ConsumerLifecycle;
}