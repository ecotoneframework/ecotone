<?php

namespace Messaging\Channel\Dispatcher;

use Messaging\Channel\MessageDispatcher;
use Messaging\Channel\MessageDispatchingException;
use Messaging\Message;
use Messaging\MessageHandler;

/**
 * Class BroadcastingDispatcher
 * @package Messaging\Channel\Dispatcher
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class BroadcastingDispatcher implements MessageDispatcher
{
    /**
     * @var MessageHandler[]
     */
    private $messageHandlers;

    public static function create() : self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function addHandler(MessageHandler $messageHandler): void
    {
        $this->messageHandlers[] = $messageHandler;
    }

    /**
     * @inheritDoc
     */
    public function removeHandler(MessageHandler $messageHandler): void
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

    /**
     * @inheritDoc
     */
    public function dispatch(Message $message): void
    {
        if (!$this->messageHandlers) {
            throw MessageDispatchingException::create("There is no message handler registered for dispatching");
        }

        foreach ($this->messageHandlers as $messageHandler) {
            $messageHandler->handle($message);
        }
    }

    /**
     * @inheritDoc
     */
    public function getHandlerCount(): int
    {
        return count($this->messageHandlers);
    }
}