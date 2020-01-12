<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\PollingConsumer;

use Ecotone\Messaging\Channel\DirectChannel;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Channel\MessageChannelInterceptorAdapter;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Endpoint\ConsumerLifecycle;
use Ecotone\Messaging\Endpoint\EntrypointGateway;
use Ecotone\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapter;
use Ecotone\Messaging\Endpoint\InterceptedMessageHandlerConsumerBuilder;
use Ecotone\Messaging\Endpoint\MessageHandlerConsumerBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Scheduling\EpochBasedClock;
use Ecotone\Messaging\Scheduling\PeriodicTrigger;
use Ecotone\Messaging\Scheduling\SyncTaskScheduler;
use Ecotone\Messaging\Support\Assert;
use Ramsey\Uuid\Uuid;

/**
 * Class PollingConsumerBuilder
 * @package Ecotone\Messaging\Endpoint\PollingConsumer
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PollingConsumerBuilder extends InterceptedMessageHandlerConsumerBuilder implements MessageHandlerConsumerBuilder
{
    /**
     * @var GatewayProxyBuilder
     */
    private $entrypointGateway;
    /**
     * @var string
     */
    private $requestChannelName;

    public function __construct()
    {
        $this->requestChannelName = Uuid::uuid4()->toString();
        $this->entrypointGateway = GatewayProxyBuilder::create(
            "handler",
            EntrypointGateway::class,
            "executeEntrypoint",
            $this->requestChannelName
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
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return [$interfaceToCallRegistry->getFor(EntrypointGateway::class, "executeEntrypoint")];
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
                    $this->requestChannelName => $connectionChannel
                ])
            );


        $inboundChannelAdapter = new InboundChannelAdapter(
            $messageHandlerBuilder->getEndpointId(),
            SyncTaskScheduler::createWithEmptyTriggerContext(new EpochBasedClock()),
            PeriodicTrigger::create(1, 0),
            new PollerTaskExecutor($messageHandlerBuilder->getEndpointId(), $messageHandlerBuilder->getInputMessageChannelName(), $pollableChannel, $gateway)
        );

        return $inboundChannelAdapter;
    }

    /**
     * @inheritDoc
     */
    public function isSupporting(MessageHandlerBuilder $messageHandlerBuilder, MessageChannelBuilder $relatedMessageChannel): bool
    {
        return $relatedMessageChannel instanceof SimpleMessageChannelBuilder && $relatedMessageChannel->isPollable();
    }
}