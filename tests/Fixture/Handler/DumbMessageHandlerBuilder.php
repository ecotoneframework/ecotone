<?php

namespace Fixture\Handler;

use Messaging\Handler\ChannelResolver;
use Messaging\Handler\MessageHandlerBuilder;
use Messaging\MessageChannel;
use Messaging\MessageHandler;

/**
 * Class DumbMessageHandlerBuilder
 * @package Fixture\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DumbMessageHandlerBuilder implements MessageHandlerBuilder
{
    /**
     * @var MessageHandler
     */
    private $messageHandler;
    /**
     * @var MessageChannel
     */
    private $messageChannel;
    /**
     * @var string
     */
    private $messageHandlerName;

    /**
     * DumbMessageHandlerBuilder constructor.
     * @param string $messageHandlerName
     * @param MessageHandler $messageHandler
     * @param MessageChannel $messageChannel
     */
    private function __construct(string $messageHandlerName, MessageHandler $messageHandler, MessageChannel $messageChannel)
    {
        $this->messageHandlerName = $messageHandlerName;
        $this->messageHandler = $messageHandler;
        $this->messageChannel = $messageChannel;
    }

    /**
     * @param string $messageHandlerName
     * @param MessageHandler $messageHandler
     * @param MessageChannel $messageChannel
     * @return DumbMessageHandlerBuilder
     */
    public static function create(string $messageHandlerName, MessageHandler $messageHandler, MessageChannel $messageChannel) : self
    {
        return new self($messageHandlerName, $messageHandler, $messageChannel);
    }

    /**
     * @inheritDoc
     */
    public function build(): MessageHandler
    {
        return $this->messageHandler;
    }

    /**
     * @inheritDoc
     */
    public function getInputMessageChannel(): MessageChannel
    {
        return $this->messageChannel;
    }

    /**
     * @inheritDoc
     */
    public function messageHandlerName(): string
    {
        return $this->messageHandlerName;
    }

    /**
     * @inheritDoc
     */
    public function setChannelResolver(ChannelResolver $channelResolver): MessageHandlerBuilder
    {
        return $this;
    }
}