<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Endpoint\PollingConsumer;

use SimplyCodedSoftware\Messaging\Channel\DirectChannel;
use SimplyCodedSoftware\Messaging\Channel\MessageChannelAdapter;
use SimplyCodedSoftware\Messaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerLifecycle;
use SimplyCodedSoftware\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapter;
use SimplyCodedSoftware\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapterBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\MessageHandlerConsumerBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\PollingConsumer\MessageDrivenChannelAdapter\MessageDrivenChannelAdapter;
use SimplyCodedSoftware\Messaging\Endpoint\PollingMetadata;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\PollableChannel;
use SimplyCodedSoftware\Messaging\Scheduling\CronTrigger;
use SimplyCodedSoftware\Messaging\Scheduling\PeriodicTrigger;
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

        if ($messageChannel instanceof MessageChannelAdapter) {
            return $messageChannel->getInternalMessageChannel() instanceof PollableChannel && !($messageChannel->getInternalMessageChannel() instanceof MessageDrivenChannelAdapter);
        }

        return $messageChannel instanceof PollableChannel && !($messageChannel instanceof MessageDrivenChannelAdapter);
    }

    /**
     * @inheritDoc
     */
    public function create(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, MessageHandlerBuilder $messageHandlerBuilder, ?PollingMetadata $pollingMetadata): ConsumerLifecycle
    {
        Assert::notNullAndEmpty($messageHandlerBuilder->getEndpointId(), "Message Endpoint name can't be empty for {$messageHandlerBuilder}");
        Assert::notNull($pollingMetadata, "No polling meta data defined for polling endpoint {$messageHandlerBuilder}");

        $messageHandler = $messageHandlerBuilder->build($channelResolver, $referenceSearchService);
        $connectionChannel = DirectChannel::create();
        $connectionChannel->subscribe($messageHandler);

        $pollableChannel = $channelResolver->resolve($messageHandlerBuilder->getInputMessageChannelName());
        Assert::isTrue($pollableChannel instanceof PollableChannel, "Channel passed to Polling Consumer must be pollable");

        $gateway = GatewayProxyBuilder::create(
            "handler",
            EntrypointGateway::class,
            "execute",
            "inputChannel"
        )
            ->build(
                InMemoryReferenceSearchService::createWithReferenceService(
                    $referenceSearchService, [
                        "handler" => $messageHandler
                    ]
                ),
                InMemoryChannelResolver::createFromAssociativeArray([
                    "inputChannel" => $connectionChannel
                ])
        );
        Assert::isTrue(\assert($gateway instanceof EntrypointGateway), "Internal error, wrong class, expected " . EntrypointGateway::class);

        return InboundChannelAdapterBuilder::createWithTaskExecutor(
            new ChannelBridgeTaskExecutor(
                $pollableChannel,
                $gateway
            )
        )
            ->withConsumerName($messageHandlerBuilder->getEndpointId())
            ->withTransactionFactories($pollingMetadata->getTransactionFactoryReferenceNames())
            ->withErrorChannel($pollingMetadata->getErrorChannelName())
            ->withTrigger(
                $pollingMetadata->getCron()
                ? CronTrigger::createWith($pollingMetadata->getCron())
                : PeriodicTrigger::create($pollingMetadata->getFixedRateInMilliseconds(), $pollingMetadata->getInitialDelayInMilliseconds())
            )
            ->build($channelResolver, $referenceSearchService);
    }
}