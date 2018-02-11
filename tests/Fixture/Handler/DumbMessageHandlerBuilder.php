<?php

namespace Fixture\Handler;

use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\Messaging\Handler\MethodParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\MessageHandler;

/**
 * Class DumbMessageHandlerBuilder
 * @package Fixture\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DumbMessageHandlerBuilder implements MessageHandlerBuilderWithParameterConverters
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
     * @var string[]
     */
    private $requiredReferenceNames = [];

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

        $this->requiredReferenceNames = [get_class($this->messageHandler)];
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

    public static function createSimple() : self
    {
        return new self("handler", NoReturnMessageHandler::create(), "inputChannel");
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService) : MessageHandler
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
    public function getRequiredReferenceNames(): array
    {
        return $this->requiredReferenceNames;
    }

    /**
     * @inheritDoc
     */
    public function registerRequiredReference(string $referenceName): void
    {
        $this->requiredReferenceNames[] = $referenceName;
    }

    /**
     * @inheritDoc
     */
    public function withMethodParameterConverters(array $methodParameterConverterBuilders): void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function getConsumerName(): string
    {
        return $this->messageHandlerName;
    }

    public function __toString()
    {
        return "dumb message handler builder";
    }
}