<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Handler;

use SimplyCodedSoftware\Messaging\Config\ReferenceTypeFromNameResolver;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCallRegistry;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\Messaging\Handler\ParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\MessageHandler;

/**
 * Class DumbMessageHandlerBuilder
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DumbMessageHandlerBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithParameterConverters
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
    public function resolveRelatedReferences(InterfaceToCallRegistry $interfaceToCallRegistry) : iterable
    {
        return [];
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
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor(MessageHandler::class, "handle");
    }

    /**
     * @inheritDoc
     */
    public function getParameterConverters(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function withMethodParameterConverters(array $methodParameterConverterBuilders)
    {
        return $this;
    }


    public function __toString()
    {
        return "dumb message handler builder";
    }
}