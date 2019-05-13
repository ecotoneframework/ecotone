<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Endpoint\PollingConsumer;

use SimplyCodedSoftware\Messaging\Channel\DirectChannel;
use SimplyCodedSoftware\Messaging\Channel\MessageChannelInterceptorAdapter;
use SimplyCodedSoftware\Messaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerLifecycle;
use SimplyCodedSoftware\Messaging\Endpoint\EntrypointGateway;
use SimplyCodedSoftware\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapter;
use SimplyCodedSoftware\Messaging\Endpoint\InterceptedMessageHandlerConsumerBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\MessageHandlerConsumerBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\PollingMetadata;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\PollableChannel;
use SimplyCodedSoftware\Messaging\Scheduling\EpochBasedClock;
use SimplyCodedSoftware\Messaging\Scheduling\PeriodicTrigger;
use SimplyCodedSoftware\Messaging\Scheduling\SyncTaskScheduler;
use SimplyCodedSoftware\Messaging\Support\Assert;

/**
 * Class PollingConsumerBuilder
 * @package SimplyCodedSoftware\Messaging\Endpoint\PollingConsumer
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PollingConsumerBuilder implements MessageHandlerConsumerBuilder
{
    /**
     * @inheritDoc
     */
    public function isSupporting(ChannelResolver $channelResolver, MessageHandlerBuilder $messageHandlerBuilder): bool
    {
        $messageChannel = $channelResolver->resolve($messageHandlerBuilder->getInputMessageChannelName());

        if ($messageChannel instanceof MessageChannelInterceptorAdapter) {
            return $messageChannel->getInternalMessageChannel() instanceof PollableChannel && !($messageChannel->getInternalMessageChannel() instanceof MessageDrivenChannelAdapter);
        }

        return $messageChannel instanceof PollableChannel;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, MessageHandlerBuilder $messageHandlerBuilder, PollingMetadata $pollingMetadata): ConsumerLifecycle
    {
        Assert::notNullAndEmpty($messageHandlerBuilder->getEndpointId(), "Message Endpoint name can't be empty for {$messageHandlerBuilder}");
        Assert::notNull($pollingMetadata, "No polling meta data defined for polling endpoint {$messageHandlerBuilder}");

        $messageHandler = $messageHandlerBuilder->build($channelResolver, $referenceSearchService);
        $connectionChannel = DirectChannel::create();
        $connectionChannel->subscribe($messageHandler);

        $pollableChannel = $channelResolver->resolve($messageHandlerBuilder->getInputMessageChannelName());
        Assert::isTrue($pollableChannel instanceof PollableChannel, "Channel passed to Polling Consumer must be pollable");

        $gatewayProxyBuilder = GatewayProxyBuilder::create(
            "handler",
            EntrypointGateway::class,
            "execute",
            "inputChannel"
        );

        $gateway = $gatewayProxyBuilder
            ->withErrorChannel($pollingMetadata->getErrorChannelName())
            ->build(
                InMemoryReferenceSearchService::createWithReferenceService(
                    $referenceSearchService, [
                        "handler" => $messageHandler
                    ]
                ),
                InMemoryChannelResolver::createWithChannelResolver($channelResolver, [
                    "inputChannel" => $connectionChannel
                ])
            );


        $inboundChannelAdapter = new InboundChannelAdapter(
            $messageHandlerBuilder->getEndpointId(),
            SyncTaskScheduler::createWithEmptyTriggerContext(new EpochBasedClock()),
            PeriodicTrigger::create(1, 0),
            new PollerTaskExecutor($pollableChannel, $gateway)
        );

        return $inboundChannelAdapter;
    }
}