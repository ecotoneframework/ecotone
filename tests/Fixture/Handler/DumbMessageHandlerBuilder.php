<?php

namespace Fixture\Handler;

use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\MessageHandler;

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
     * @var string
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
     * @param string $inputMessageChannelName
     */
    private function __construct(string $messageHandlerName, MessageHandler $messageHandler, string $inputMessageChannelName)
    {
        $this->messageHandlerName = $messageHandlerName;
        $this->messageHandler = $messageHandler;
        $this->messageChannel = $inputMessageChannelName;
    }

    /**
     * @param string $messageHandlerName
     * @param MessageHandler $messageHandler
     * @param string $inputMessageChannelName
     * @return DumbMessageHandlerBuilder
     */
    public static function create(string $messageHandlerName, MessageHandler $messageHandler, string $inputMessageChannelName) : self
    {
        return new self($messageHandlerName, $messageHandler, $inputMessageChannelName);
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
    public function getInputMessageChannelName(): string
    {
        return $this->messageChannel;
    }

    /**
     * @inheritDoc
     */
    public function getConsumerName(): string
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

    /**
     * @inheritDoc
     */
    public function setReferenceSearchService(ReferenceSearchService $referenceSearchService): MessageHandlerBuilder
    {
        return $this;
    }

    public function __toString()
    {
        return "dumb message handler builder";
    }
}