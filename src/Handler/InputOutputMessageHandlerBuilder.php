<?php

namespace Messaging\Handler;

use Messaging\MessageChannel;

/**
 * Class InputOutputMessageHandlerBuilder
 * @package Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class InputOutputMessageHandlerBuilder implements MessageHandlerBuilder
{
    /**
     * @var MessageChannel
     */
    protected $inputMessageChannel;
    /**
     * @var MessageChannel
     */
    protected $outputMessageChannel;
    /**
     * @var string
     */
    protected $messageHandlerName;

    /**
     * @param string $name
     * @return self|static
     */
    public function withName(string $name) : self
    {
        $this->messageHandlerName = $name;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function messageHandlerName(): string
    {
        return $this->messageHandlerName;
    }

    /**
     * @param MessageChannel $messageChannel
     * @return self|static
     */
    public function withInputMessageChannel(MessageChannel $messageChannel) : self
    {
        $this->inputMessageChannel = $messageChannel;

        return $this;
    }

    /**
     * @param MessageChannel $messageChannel
     * @return self|static
     */
    public function withOutputMessageChannel(MessageChannel $messageChannel) : self
    {
        $this->outputMessageChannel = $messageChannel;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getInputMessageChannel(): MessageChannel
    {
        return $this->inputMessageChannel;
    }
}