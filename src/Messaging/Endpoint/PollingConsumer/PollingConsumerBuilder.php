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
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCallRegistry;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
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
class PollingConsumerBuilder extends InterceptedMessageHandlerConsumerBuilder implements MessageHandlerConsumerBuilder
{
    /**
     * @var GatewayProxyBuilder
     */
    private $entrypointGateway;

    public function __construct()
    {
        $this->entrypointGateway = GatewayProxyBuilder::create(
            "handler",
            EntrypointGateway::class,
            "executeEntrypoint",
            "inputChannel"
        );
    }

    /**
     * @inheritDoc
     */
    public function addAroundInterceptor(AroundInterceptorReference $aroundInterceptorReference)
    {
        $this->entrypointGateway->addAroundInterceptor($aroundInterceptorReference);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $this->entrypointGateway->getInterceptedInterface($interfaceToCallRegistry);
    }

    /**
     * @inheritDoc
     */
    public function withEndpointAnnotations(iterable $endpointAnnotations)
    {
        $this->entrypointGateway->withEndpointAnnotations($endpointAnnotations);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getEndpointAnnotations(): array
    {
        return $this->entrypointGateway->getEndpointAnnotations();
    }

    /**
     * @inheritDoc
     */
    public function getRequiredInterceptorNames(): iterable
    {
        return $this->entrypointGateway->getRequiredInterceptorNames();
    }

    /**
     * @inheritDoc
     */
    public function withRequiredInterceptorNames(iterable $interceptorNames)
    {
        $this->entrypointGateway->withRequiredInterceptorNames($interceptorNames);

        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function buildAdapter(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, MessageHandlerBuilder $messageHandlerBuilder, PollingMetadata $pollingMetadata): ConsumerLifecycle
    {
        Assert::notNullAndEmpty($messageHandlerBuilder->getEndpointId(), "Message Endpoint name can't be empty for {$messageHandlerBuilder}");
        Assert::notNull($pollingMetadata, "No polling meta data defined for polling endpoint {$messageHandlerBuilder}");

        $messageHandler = $messageHandlerBuilder->build($channelResolver, $referenceSearchService);
        $connectionChannel = DirectChannel::create();
        $connectionChannel->subscribe($messageHandler);

        $pollableChannel = $channelResolver->resolve($messageHandlerBuilder->getInputMessageChannelName());
        Assert::isTrue($pollableChannel instanceof PollableChannel, "Channel passed to Polling Consumer must be pollable");

        $gateway = $this->entrypointGateway
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

    /**
     * @inheritDoc
     */
    public function isSupporting(ChannelResolver $channelResolver, MessageHandlerBuilder $messageHandlerBuilder): bool
    {
        return false;
    }
}