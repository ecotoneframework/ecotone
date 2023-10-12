<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\PollingConsumer;

use Ecotone\Messaging\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Messaging\Channel\DirectChannel;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
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
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Scheduling\EpochBasedClock;
use Ecotone\Messaging\Scheduling\PeriodicTrigger;
use Ecotone\Messaging\Scheduling\SyncTaskScheduler;
use Ecotone\Messaging\Support\Assert;
use Ramsey\Uuid\Uuid;

/**
 * This class is stateless Service which creates Message Consumers for Message Handlers.
 * It should not hold any state, as it will be reused for different endpoints.
 */
class PollingConsumerBuilder extends InterceptedMessageHandlerConsumerBuilder implements MessageHandlerConsumerBuilder
{
    /**
     * @param AroundInterceptorReference[] $aroundInterceptorReferences
     * @param MethodInterceptor[] $beforeInterceptors
     * @param MethodInterceptor[] $afterInterceptors
     * @param object[] $endpointAnnotations
     */
    public function __construct(
        private array $aroundInterceptorReferences = [],
        private array $beforeInterceptors = [],
        private array $afterInterceptors = [],
        private array $endpointAnnotations = [],
    ) {

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
        $this->aroundInterceptorReferences[] = $aroundInterceptorReference;

        return $this;
    }

    /**
     * @param MethodInterceptor $methodInterceptor
     * @return $this
     */
    public function addBeforeInterceptor(MethodInterceptor $methodInterceptor): self
    {
        $this->beforeInterceptors[] = $methodInterceptor;

        return $this;
    }

    /**
     * @param MethodInterceptor $methodInterceptor
     * @return $this
     */
    public function addAfterInterceptor(MethodInterceptor $methodInterceptor): self
    {
        $this->afterInterceptors[] = $methodInterceptor;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor(InboundChannelAdapterEntrypoint::class, 'executeEntrypoint');
    }

    /**
     * @inheritDoc
     */
    public function withEndpointAnnotations(iterable $endpointAnnotations): self
    {
        $this->endpointAnnotations = $endpointAnnotations;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getEndpointAnnotations(): array
    {
        return $this->endpointAnnotations;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredInterceptorNames(): iterable
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return [
            $interfaceToCallRegistry->getFor(InboundGatewayEntrypoint::class, 'executeEntrypoint'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function withRequiredInterceptorNames(iterable $interceptorNames): self
    {
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

        $requestChannelName = Uuid::uuid4()->toString();
        $entrypointGateway = GatewayProxyBuilder::create(
            'handler',
            InboundChannelAdapterEntrypoint::class,
            'executeEntrypoint',
            $requestChannelName
        );
        $entrypointGateway->withEndpointAnnotations(array_merge(
            $this->endpointAnnotations,
            [new AsynchronousRunningEndpoint($messageHandlerBuilder->getEndpointId())]
        ));
        foreach ($this->beforeInterceptors as $beforeInterceptor) {
            $entrypointGateway->addBeforeInterceptor($beforeInterceptor);
        }
        foreach ($this->aroundInterceptorReferences as $aroundInterceptorReference) {
            $entrypointGateway->addAroundInterceptor($aroundInterceptorReference);
        }
        $entrypointGateway->addAroundInterceptor(AcknowledgeConfirmationInterceptor::createAroundInterceptor(
            $referenceSearchService->get(InterfaceToCallRegistry::REFERENCE_NAME),
            $pollingMetadata
        ));
        foreach ($this->afterInterceptors as $afterInterceptor) {
            $entrypointGateway->addAfterInterceptor($afterInterceptor);
        }

        $messageHandler = $messageHandlerBuilder->build($channelResolver, $referenceSearchService);
        $connectionChannel = DirectChannel::create();
        $connectionChannel->subscribe($messageHandler);

        $pollableChannel = $channelResolver->resolve($messageHandlerBuilder->getInputMessageChannelName());
        Assert::isTrue($pollableChannel instanceof PollableChannel, 'Channel passed to Polling Consumer must be pollable');

        $gateway = $entrypointGateway
            ->withErrorChannel($pollingMetadata->getErrorChannelName())
            ->buildWithoutProxyObject(
                $referenceSearchService,
                InMemoryChannelResolver::createWithChannelResolver($channelResolver, [
                    $requestChannelName => $connectionChannel,
                ])
            );

        return new InboundChannelAdapter(
            $messageHandlerBuilder->getEndpointId(),
            SyncTaskScheduler::createWithEmptyTriggerContext(new EpochBasedClock(), $pollingMetadata),
            PeriodicTrigger::create(1, 0),
            new PollerTaskExecutor($messageHandlerBuilder->getEndpointId(), $messageHandlerBuilder->getInputMessageChannelName(), $pollableChannel, $gateway)
        );
    }
}
