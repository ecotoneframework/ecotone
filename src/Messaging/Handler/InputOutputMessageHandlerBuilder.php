<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\Config\Container\AttributeDefinition;
use Ecotone\Messaging\Support\Assert;

/**
 * Class InputOutputMessageHandlerBuilder
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
abstract class InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithOutputChannel
{
    protected string $inputMessageChannelName = '';
    protected string $outputMessageChannelName = '';
    private ?string $endpointId = '';
    /**
     * @var string[]
     */
    protected iterable $requiredInterceptorReferenceNames = [];
    /**
     * @var object[]
     */
    private iterable $endpointAnnotations = [];

    /**
     * @inheritDoc
     */
    public function withOutputMessageChannel(string $messageChannelName): self
    {
        $self = clone $this;
        $self->outputMessageChannelName = $messageChannelName;

        return $self;
    }

    /**
     * @inheritDoc
     */
    public function getOutputMessageChannelName(): string
    {
        return $this->outputMessageChannelName;
    }

    /**
     * @inheritDoc
     */
    public function withInputChannelName(string $inputChannelName): self
    {
        $self = clone $this;
        $self->inputMessageChannelName = $inputChannelName;

        return $self;
    }

    /**
     * @inheritDoc
     */
    public function getInputMessageChannelName(): string
    {
        return $this->inputMessageChannelName;
    }

    /**
     * @inheritDoc
     */
    public function withEndpointAnnotations(iterable $endpointAnnotations): self
    {
        Assert::allInstanceOfType($endpointAnnotations, AttributeDefinition::class);
        $self = clone $this;
        $self->endpointAnnotations = $endpointAnnotations;

        return $self;
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
        return $this->requiredInterceptorReferenceNames;
    }

    /**
     * @param string[] $interceptorNames
     *
     * @return static
     */
    public function withRequiredInterceptorNames(iterable $interceptorNames): self
    {
        $this->requiredInterceptorReferenceNames = $interceptorNames;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getEndpointId(): ?string
    {
        return $this->endpointId;
    }

    /**
     * @inheritDoc
     */
    public function withEndpointId(string $endpointId): self
    {
        $this->endpointId = $endpointId;

        return $this;
    }

    public function __toString()
    {
        return sprintf('Handler of type %s with name `%s` for input channel `%s`', get_class($this), $this->getEndpointId(), $this->getInputMessageChannelName());
    }
}
