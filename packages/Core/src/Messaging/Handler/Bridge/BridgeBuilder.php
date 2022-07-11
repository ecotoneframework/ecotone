<?php

namespace Ecotone\Messaging\Handler\Bridge;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\MessageHandler;

/**
 * Class BridgeBuilder
 * @package Ecotone\Messaging\Handler\Bridge
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class BridgeBuilder implements MessageHandlerBuilderWithOutputChannel
{
    private \Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder $bridgeBuilder;

    private function __construct()
    {
        $this->bridgeBuilder = ServiceActivatorBuilder::createWithDirectReference(new Bridge(), 'handle');
    }

    /**
     * @inheritDoc
     */
    public function addAroundInterceptor(AroundInterceptorReference $aroundInterceptorReference): ServiceActivatorBuilder
    {
        $self = clone $this;

        return $self->bridgeBuilder->addAroundInterceptor($aroundInterceptorReference);
    }

    /**
     * @inheritDoc
     */
    public function withEndpointAnnotations(iterable $endpointAnnotations): ServiceActivatorBuilder
    {
        $self = clone $this;

        return $self->bridgeBuilder->withEndpointAnnotations($endpointAnnotations);
    }

    /**
     * @inheritDoc
     */
    public function getEndpointAnnotations(): array
    {
        return $this->bridgeBuilder->getEndpointAnnotations();
    }

    /**
     * @inheritDoc
     */
    public function getRequiredInterceptorNames(): iterable
    {
        return $this->bridgeBuilder->getRequiredInterceptorNames();
    }

    /**
     * @inheritDoc
     */
    public function withRequiredInterceptorNames(iterable $interceptorNames): ServiceActivatorBuilder
    {
        return $this->bridgeBuilder->withRequiredInterceptorNames($interceptorNames);
    }

    /**
     * @inheritDoc
     */
    public function withInputChannelName(string $inputChannelName): ServiceActivatorBuilder
    {
        $self = clone $this;

        return $self->bridgeBuilder->withInputChannelName($inputChannelName);
    }

    /**
     * @inheritDoc
     */
    public function getEndpointId(): ?string
    {
        return $this->bridgeBuilder->getEndpointId();
    }

    /**
     * @inheritDoc
     */
    public function withEndpointId(string $endpointId): ServiceActivatorBuilder
    {
        return $this->bridgeBuilder->withEndpointId($endpointId);
    }

    /**
     * @inheritDoc
     */
    public function getInputMessageChannelName(): string
    {
        return $this->bridgeBuilder->getInputMessageChannelName();
    }

    /**
     * @inheritDoc
     */
    public function withOutputMessageChannel(string $messageChannelName): ServiceActivatorBuilder
    {
        $self = clone $this;

        return $self->bridgeBuilder->withOutputMessageChannel($messageChannelName);
    }

    /**
     * @inheritDoc
     */
    public function getOutputMessageChannelName(): string
    {
        return $this->bridgeBuilder->getOutputMessageChannelName();
    }


    /**
     * @return BridgeBuilder
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $this->bridgeBuilder->getInterceptedInterface($interfaceToCallRegistry);
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        return $this->bridgeBuilder->build($channelResolver, $referenceSearchService);
    }

    /**
     * @inheritDoc
     */
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return $this->bridgeBuilder->resolveRelatedInterfaces($interfaceToCallRegistry);
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return $this->bridgeBuilder->getRequiredReferenceNames();
    }
}
