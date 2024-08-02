<?php

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\SubscribableChannel;

/**
 * Class PublishSubscribeChannel
 * @package Ecotone\Messaging\Channel
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class PublishSubscribeChannel implements SubscribableChannel, DefinedObject
{
    /**
     * @var MessageHandler[]
     */
    private array $messageHandlers = [];

    public function __construct(private string $messageChannelName)
    {
    }

    public static function create(string $messageChannelName = ''): self
    {
        return new self($messageChannelName);
    }

    /**
     * @inheritDoc
     */
    public function send(Message $message): void
    {
        foreach ($this->messageHandlers as $messageHandler) {
            $messageHandler->handle($message);
        }
    }

    /**
     * @inheritDoc
     */
    public function subscribe(MessageHandler $messageHandler): void
    {
        $this->messageHandlers[] = $messageHandler;
    }

    /**
     * @inheritDoc
     */
    public function unsubscribe(MessageHandler $messageHandler): void
    {
        $handlers = [];
        foreach ($this->messageHandlers as $messageHandlerToCompare) {
            if ($messageHandlerToCompare === $messageHandler) {
                continue;
            }

            $handlers[] = $messageHandlerToCompare;
        }

        $this->messageHandlers = $handlers;
    }

    public function __toString()
    {
        return 'publish subscribe: ' . $this->messageChannelName;
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [$this->messageChannelName]);
    }
}
