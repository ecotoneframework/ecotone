<?php

declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler;

use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;

/**
 * Class InputOutputMessageHandlerBuilder
 * @package SimplyCodedSoftware\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithOutputChannel
{
    /**
     * @var string
     */
    protected $inputMessageChannelName = "";
    /**
     * @var string
     */
    protected $outputMessageChannelName = "";
    /**
     * @var string
     */
    private $name = "";
    /**
     * @var string[]
     */
    private $requiredInterceptorReferenceNames = [];
    /**
     * @var AroundInterceptorReference[]
     */
    protected $orderedAroundInterceptors = [];
    /**
     * @var object[]
     */
    private $endpointAnnotations = [];

    /**
     * @inheritDoc
     */
    public function withOutputMessageChannel(string $messageChannelName)
    {
        $this->outputMessageChannelName = $messageChannelName;

        return $this;
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
    public function withInputChannelName(string $inputChannelName)
    {
        $this->inputMessageChannelName = $inputChannelName;

        return $this;
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
    public function withEndpointAnnotations(iterable $endpointAnnotations)
    {
        $this->endpointAnnotations = $endpointAnnotations;

        return $this;
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
    public function addAroundInterceptor(AroundInterceptorReference $aroundInterceptorReference)
    {
        $this->orderedAroundInterceptors[] = $aroundInterceptorReference;

        return $this;
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
    public function withRequiredInterceptorNames(iterable $interceptorNames)
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
    public function withEndpointId(string $endpointId)
    {
        $this->name = $endpointId;

        return $this;
    }

    public function __toString()
    {
        return sprintf("Handler of type %s with name `%s` for input channel `%s`", get_class($this), $this->getEndpointId(), $this->getInputMessageChannelName());
    }
}