<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Endpoint\PollOrThrow;

use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\Messaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Channel\MessageChannelInterceptorAdapter;
use SimplyCodedSoftware\Messaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerLifecycle;
use SimplyCodedSoftware\Messaging\Endpoint\MessageHandlerConsumerBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\PollingMetadata;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\PollableChannel;

/**
 * Class PollOrThrowPollableFactory
 * @package SimplyCodedSoftware\Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PollOrThrowMessageHandlerConsumerBuilder implements MessageHandlerConsumerBuilder
{
    /**
     * @inheritDoc
     */
    public function addAroundInterceptor(AroundInterceptorReference $aroundInterceptorReference)
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function isSupporting(MessageHandlerBuilder $messageHandlerBuilder, MessageChannelBuilder $relatedMessageChannel): bool
    {
        return $relatedMessageChannel instanceof SimpleMessageChannelBuilder && $relatedMessageChannel->isPollable();
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, MessageHandlerBuilder $messageHandlerBuilder, PollingMetadata $pollingMetadata): ConsumerLifecycle
    {
        /** @var PollableChannel $pollableChannel */
        $pollableChannel = $channelResolver->resolve($messageHandlerBuilder->getInputMessageChannelName());

        return PollOrThrowExceptionConsumer::create($messageHandlerBuilder->getEndpointId(), $pollableChannel, $messageHandlerBuilder->build(
            $channelResolver, $referenceSearchService
        ));
    }
}