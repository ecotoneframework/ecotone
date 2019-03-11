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
    public function getRequiredInterceptorReferenceNames(): iterable
    {
        return $this->requiredInterceptorReferenceNames;
    }

    /**
     * @param string[] $referenceNames
     *
     * @return static
     */
    public function withRequiredInterceptorReferenceNames(iterable $referenceNames)
    {
        $this->requiredInterceptorReferenceNames = $referenceNames;

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