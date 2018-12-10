<?php

declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler;

/**
 * Class InputOutputMessageHandlerBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler
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