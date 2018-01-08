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
     * @var string
     */
    protected $inputMessageChannelName;
    /**
     * @var string
     */
    protected $outputMessageChannelName;
    /**
     * @var string
     */
    protected $messageHandlerName;
    /**
     * @var ChannelResolver
     */
    protected $channelResolver;

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
     * @param string $messageChannelName
     * @return self|static
     */
    public function withInputMessageChannel(string $messageChannelName) : self
    {
        $this->inputMessageChannelName = $messageChannelName;

        return $this;
    }

    /**
     * @param string $messageChannelName
     * @return self|static
     */
    public function withOutputMessageChannel(string $messageChannelName) : self
    {
        $this->outputMessageChannelName = $messageChannelName;

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
    public function setChannelResolver(ChannelResolver $channelResolver): MessageHandlerBuilder
    {
        $this->channelResolver = $channelResolver;

        return $this;
    }
}