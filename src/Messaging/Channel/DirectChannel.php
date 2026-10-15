<?php

namespace Ecotone\Messaging\Channel;

use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\SubscribableChannel;
use Ecotone\Messaging\Support\MessageDescription;

/**
 * Class DirectChannel
 * @package Ecotone\Messaging\Channel
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
final class DirectChannel implements SubscribableChannel, DefinedObject
{
    public function __construct(private string $messageChannelName, private ?MessageHandler $messageHandler = null)
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
        if (! $this->messageHandler) {
            throw MessageDispatchingException::create('There is no message handler registered for dispatching Message ' . MessageDescription::of($message));
        }

        $this->messageHandler->handle($message);
    }

    /**
     * @inheritDoc
     */
    public function subscribe(MessageHandler $messageHandler): void
    {
        if ($this->messageHandler) {
            throw WrongHandlerAmountException::forDirectChannelSharedBy($this->messageChannelName, (string) $this->messageHandler, (string) $messageHandler);
        }

        $this->messageHandler = $messageHandler;
    }

    /**
     * @inheritDoc
     */
    public function unsubscribe(MessageHandler $messageHandler): void
    {
        $this->messageHandler = null;
    }

    public function __toString()
    {
        return 'direct: ' . $this->messageChannelName;
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [$this->messageChannelName]);
    }
}
