<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Endpoint\PollingConsumer;

use SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel;
use SimplyCodedSoftware\IntegrationMessaging\Channel\MessageChannelAdapter;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\ConsumerLifecycle;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\InboundChannelAdapter\InboundChannelAdapter;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\InboundChannelAdapter\InboundChannelAdapterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\MessageHandlerConsumerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\PollingConsumer\MessageDrivenChannelAdapter\MessageDrivenChannelAdapter;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\PollingMetadata;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\PollableChannel;
use SimplyCodedSoftware\IntegrationMessaging\Scheduling\CronTrigger;
use SimplyCodedSoftware\IntegrationMessaging\Scheduling\PeriodicTrigger;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;

/**
 * Class PollingConsumerBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Endpoint\PollingConsumer
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