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
     * @param string $messageChannelName
     * @return self|static
     */
    public function withInputMessageChannel(string $messageChannelName) : self
    {
        $this->inputMessageChannelName = $messageChannelName;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withOutputMessageChannel(string $messageChannelName) : self
    {
        $this->outputMessageChannelName = $messageChannelName;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withInputChannelName(string $inputChannelName): self
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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function withName(string $messageHandlerName) : self
    {
        $this->name = $messageHandlerName;

        return $this;
    }

    public function __toString()
    {
        return sprintf("Handler of type %s with name `%s` for input channel `%s`", get_class($this), $this->getName(), $this->getInputMessageChannelName());
    }
}