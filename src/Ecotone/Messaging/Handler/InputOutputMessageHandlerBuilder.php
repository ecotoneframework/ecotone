<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundMethodInterceptor;

/**
 * Class InputOutputMessageHandlerBuilder
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithOutputChannel
{
    protected string $inputMessageChannelName = "";
    protected string $outputMessageChannelName = "";
    private ?string $name = "";
    /**
     * @var string[]
     */
    protected iterable $requiredInterceptorReferenceNames = [];
    /**
     * @var AroundInterceptorReference[]
     */
    protected array $orderedAroundInterceptors = [];
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
     * @param iterable $endpointAnnotations
     * @return static
     */
    public function withEndpointAnnotations(iterable $endpointAnnotations): self
    {
        $self = clone $this;
        $self->endpointAnnotations = $endpointAnnotations;

        return $self;
    }

    /**
     * @return object[]
     */
    public function getEndpointAnnotations(): array
    {
        return $this->endpointAnnotations;
    }

    /**
     * @inheritDoc
     */
    public function addAroundInterceptor(AroundInterceptorReference $aroundInterceptorReference): self
    {
        $self = clone $this;
        $self->orderedAroundInterceptors[] = $aroundInterceptorReference;

        return $self;
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
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function withEndpointId(string $endpointId): self
    {
        $this->name = $endpointId;

        return $this;
    }

    public function __toString()
    {
        return sprintf("Handler of type %s with name `%s` for input channel `%s`", get_class($this), $this->getEndpointId(), $this->getInputMessageChannelName());
    }
}