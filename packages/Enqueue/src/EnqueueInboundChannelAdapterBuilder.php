<?php

namespace Ecotone\Enqueue;

use Ecotone\Messaging\Endpoint\AcknowledgeConfirmationInterceptor;
use Ecotone\Messaging\Endpoint\InboundChannelAdapterEntrypoint;
use Ecotone\Messaging\Endpoint\InterceptedChannelAdapterBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Handler\ReferenceSearchService;

abstract class EnqueueInboundChannelAdapterBuilder extends InterceptedChannelAdapterBuilder
{
    public const DEFAULT_RECEIVE_TIMEOUT = 10000;

    /**
     * @var string
     */
    protected $endpointId;
    /**
     * @var int
     */
    protected $receiveTimeoutInMilliseconds = self::DEFAULT_RECEIVE_TIMEOUT;
    protected array $headerMapper = [];
    /**
     * @var string
     */
    protected $acknowledgeMode = EnqueueAcknowledgementCallback::AUTO_ACK;
    /**
     * @var InboundChannelAdapterEntrypoint|GatewayProxyBuilder
     */
    protected $inboundEntrypoint;

    protected $requiredReferenceNames = [];

    protected $withAckInterceptor = false;

    protected function initialize(string $endpointId, ?string $requestChannelName, string $connectionReferenceName): void
    {
        $this->requiredReferenceNames[] = $connectionReferenceName;
        $this->endpointId = $endpointId;
        $this->inboundEntrypoint = $requestChannelName
            ? GatewayProxyBuilder::create($endpointId, InboundChannelAdapterEntrypoint::class, 'executeEntrypoint', $requestChannelName)
            : NullEntrypointGateway::create();
    }

    protected function buildGatewayFor(ReferenceSearchService $referenceSearchService, ChannelResolver $channelResolver, PollingMetadata $pollingMetadata): InboundChannelAdapterEntrypoint
    {
        if (! $this->isNullableGateway()) {
            if ($this->withAckInterceptor) {
                $this->inboundEntrypoint->addAroundInterceptor(AcknowledgeConfirmationInterceptor::createAroundInterceptor($pollingMetadata));
            }

            return $this->inboundEntrypoint
                ->withErrorChannel($pollingMetadata->getErrorChannelName())
                ->build($referenceSearchService, $channelResolver);
        }

        return $this->inboundEntrypoint;
    }

    /**
     * @inheritDoc
     */
    public function addAroundInterceptor(AroundInterceptorReference $aroundInterceptorReference)
    {
        if ($this->isNullableGateway()) {
            return $this;
        }

        $this->inboundEntrypoint->addAroundInterceptor($aroundInterceptorReference);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        $resolvedInterfaces = $this->isNullableGateway() ? [] : $this->inboundEntrypoint->resolveRelatedInterfaces($interfaceToCallRegistry);
        $resolvedInterfaces[] = $interfaceToCallRegistry->getFor(InboundChannelAdapterEntrypoint::class, 'executeEntrypoint');

        return $resolvedInterfaces;
    }

    /**
     * @return string
     */
    public function getEndpointId(): string
    {
        return $this->endpointId;
    }

    /**
     * @param string $headerMapper
     * @return static
     */
    public function withHeaderMapper(string $headerMapper): self
    {
        $this->headerMapper = explode(',', $headerMapper);

        return $this;
    }

    /**
     * How long it should try to receive message
     *
     * @param int $timeoutInMilliseconds
     * @return static
     */
    public function withReceiveTimeout(int $timeoutInMilliseconds): self
    {
        $this->receiveTimeoutInMilliseconds = $timeoutInMilliseconds;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return array_merge($this->requiredReferenceNames, $this->isNullableGateway() ? [] : $this->inboundEntrypoint->getRequiredReferences());
    }

    /**
     * @inheritDoc
     */
    public function addBeforeInterceptor(MethodInterceptor $methodInterceptor): self
    {
        $this->inboundEntrypoint->addBeforeInterceptor($methodInterceptor);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addAfterInterceptor(MethodInterceptor $methodInterceptor): self
    {
        $this->inboundEntrypoint->addAfterInterceptor($methodInterceptor);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $this->inboundEntrypoint->getInterceptedInterface($interfaceToCallRegistry);
    }

    /**
     * @inheritDoc
     */
    public function withEndpointAnnotations(iterable $endpointAnnotations)
    {
        $this->inboundEntrypoint->withEndpointAnnotations($endpointAnnotations);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getEndpointAnnotations(): array
    {
        return $this->inboundEntrypoint->getEndpointAnnotations();
    }

    /**
     * @inheritDoc
     */
    public function getRequiredInterceptorNames(): iterable
    {
        return $this->inboundEntrypoint->getRequiredInterceptorNames();
    }

    /**
     * @inheritDoc
     */
    public function withRequiredInterceptorNames(iterable $interceptorNames)
    {
        $this->inboundEntrypoint->withRequiredInterceptorNames($interceptorNames);

        return $this;
    }

    /**
     * @return string
     */
    public function getAcknowledgeMode(): string
    {
        return $this->acknowledgeMode;
    }

    public function __toString()
    {
        return 'Inbound Adapter with id ' . $this->endpointId;
    }

    /**
     * @return bool
     */
    private function isNullableGateway(): bool
    {
        return $this->inboundEntrypoint instanceof NullEntrypointGateway;
    }
}
