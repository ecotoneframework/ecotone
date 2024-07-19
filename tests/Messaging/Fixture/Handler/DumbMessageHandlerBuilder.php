<?php

namespace Test\Ecotone\Messaging\Fixture\Handler;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\DefinedObjectWrapper;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\MessageHandler;

/**
 * Class DumbMessageHandlerBuilder
 * @package Test\Ecotone\Messaging\Fixture\Handler
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
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
     * DumbMessageHandlerBuilder constructor.
     * @param MessageHandler $messageHandler
     * @param string $inputMessageChannelName
     */
    private function __construct(MessageHandler $messageHandler, string $inputMessageChannelName)
    {
        $this->messageHandler = $messageHandler;
        $this->messageChannel = $inputMessageChannelName;
    }

    /**
     * @param MessageHandler $messageHandler
     * @param string $inputMessageChannelName
     * @return DumbMessageHandlerBuilder
     */
    public static function create(MessageHandler $messageHandler, string $inputMessageChannelName): self
    {
        return new self($messageHandler, $inputMessageChannelName);
    }

    public static function createSimple(): self
    {
        return new self(NoReturnMessageHandler::create(), 'inputChannel');
    }

    public function compile(MessagingContainerBuilder $builder): Definition
    {
        return new DefinedObjectWrapper($this->messageHandler);
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
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor(MessageHandler::class, 'handle');
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
        return 'dumb message handler builder';
    }
}
