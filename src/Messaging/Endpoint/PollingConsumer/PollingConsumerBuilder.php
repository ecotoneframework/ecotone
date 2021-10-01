<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\PollingConsumer;

use Ecotone\Enqueue\EnqueueAcknowledgeConfirmationInterceptor;
use Ecotone\Messaging\Channel\DirectChannel;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Endpoint\AcknowledgeConfirmationInterceptor;
use Ecotone\Messaging\Endpoint\ConsumerLifecycle;
use Ecotone\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapter;
use Ecotone\Messaging\Endpoint\InboundChannelAdapterEntrypoint;
use Ecotone\Messaging\Endpoint\InboundGatewayEntrypoint;
use Ecotone\Messaging\Endpoint\InterceptedMessageHandlerConsumerBuilder;
use Ecotone\Messaging\Endpoint\MessageHandlerConsumerBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Gateway\ErrorChannelInterceptor;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Logger\LoggingInterceptor;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Precedence;
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
    private \Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder $entrypointGateway;
    private string $requestChannelName;

    public function __construct()
    {
        $this->requestChannelName = Uuid::uuid4()->toString();
        $this->entrypointGateway = GatewayProxyBuilder::create(
            "handler",
            InboundChannelAdapterEntrypoint::class,
            "executeEntrypoint",
            $this->requestChannelName
        );

        $this->entrypointGateway->addAroundInterceptor(AroundInterceptorReference::createWithDirectObjectAndResolveConverters(
            new LoggingInterceptor(null),
            "logException",
            Precedence::EXCEPTION_LOGGING_PRECEDENCE,
            ""
        ));
    }

    public function isPollingConsumer(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function addAroundInterceptor(AroundInterceptorReference $aroundInterceptorReference): self
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
    public function withEndpointAnnotations(iterable $endpointAnnotations): self
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
        return array_merge([
            $interfaceToCallRegistry->getFor(InboundGatewayEntrypoint::class, "executeEntrypoint"),
        ], $this->entrypointGateway->resolveRelatedInterfaces($interfaceToCallRegistry));
    }

    /**
     * @inheritDoc
     */
    public function withRequiredInterceptorNames(iterable $interceptorNames): self
    {
        $this->entrypointGateway->withRequiredInterceptorNames($interceptorNames);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isSupporting(MessageHandlerBuilder $messageHandlerBuilder, MessageChannelBuilder $relatedMessageChannel): bool
    {
        return $relatedMessageChannel->isPollable();
    }

    /**
     * @inheritDoc
     */
    protected function buildAdapter(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, MessageHandlerBuilder $messageHandlerBuilder, PollingMetadata $pollingMetadata): ConsumerLifecycle
    {
        Assert::notNullAndEmpty($messageHandlerBuilder->getEndpointId(), "Message Endpoint name can't be empty for {$messageHandlerBuilder}");
        Assert::notNull($pollingMetadata, "No polling meta data defined for polling endpoint {$messageHandlerBuilder}");

        $this->entrypointGateway->addAroundInterceptor(AcknowledgeConfirmationInterceptor::createAroundInterceptor($pollingMetadata));

        $messageHandler = $messageHandlerBuilder->build($channelResolver, $referenceSearchService);
        $connectionChannel = DirectChannel::create();
        $connectionChannel->subscribe($messageHandler);

        $pollableChannel = $channelResolver->resolve($messageHandlerBuilder->getInputMessageChannelName());
        Assert::isTrue($pollableChannel instanceof PollableChannel, "Channel passed to Polling Consumer must be pollable");

        $gateway = $this->entrypointGateway
            ->withErrorChannel($pollingMetadata->getErrorChannelName())
            ->buildWithoutProxyObject(
                $referenceSearchService,
                InMemoryChannelResolver::createWithChannelResolver($channelResolver, [
                    $this->requestChannelName => $connectionChannel
                ])
            );

        return new InboundChannelAdapter(
            $messageHandlerBuilder->getEndpointId(),
            SyncTaskScheduler::createWithEmptyTriggerContext(new EpochBasedClock()),
            PeriodicTrigger::create(1, 0),
            new PollerTaskExecutor($messageHandlerBuilder->getEndpointId(), $messageHandlerBuilder->getInputMessageChannelName(), $pollableChannel, $gateway)
        );
    }
}