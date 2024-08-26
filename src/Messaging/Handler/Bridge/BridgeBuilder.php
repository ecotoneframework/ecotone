<?php

namespace Ecotone\Messaging\Handler\Bridge;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;

/**
 * Class BridgeBuilder
 * @package Ecotone\Messaging\Handler\Bridge
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class BridgeBuilder implements MessageHandlerBuilderWithOutputChannel
{
    private ServiceActivatorBuilder $bridgeBuilder;

    private function __construct()
    {
        $this->bridgeBuilder = ServiceActivatorBuilder::create(Bridge::class, new InterfaceToCallReference(Bridge::class, 'handle'));
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

    public function compile(MessagingContainerBuilder $builder): Definition|Reference
    {
        return $this->bridgeBuilder->compile($builder);
    }
}
