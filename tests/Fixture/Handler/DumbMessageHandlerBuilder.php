<?php

namespace Fixture\Handler;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;

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
     * @var string[]
     */
    private $requiredReferenceNames = [];

    /**
     * DumbMessageHandlerBuilder constructor.
     * @param MessageHandler $messageHandler
     * @param string $inputMessageChannelName
     */
    private function __construct(MessageHandler $messageHandler, string $inputMessageChannelName)
    {
        $this->messageHandler = $messageHandler;
        $this->messageChannel = $inputMessageChannelName;

        $this->requiredReferenceNames = [get_class($this->messageHandler)];
    }

    /**
     * @param MessageHandler $messageHandler
     * @param string $inputMessageChannelName
     * @return DumbMessageHandlerBuilder
     */
    public static function create(MessageHandler $messageHandler, string $inputMessageChannelName) : self
    {
        return new self($messageHandler, $inputMessageChannelName);
    }

    public static function createSimple() : self
    {
        return new self(NoReturnMessageHandler::create(), "inputChannel");
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
    public function withInputChannelName(string $inputChannelName): self
    {
        return $this;
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


    public function __toString()
    {
        return "dumb message handler builder";
    }
}