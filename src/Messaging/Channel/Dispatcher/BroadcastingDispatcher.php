<?php

namespace Ecotone\Messaging\Channel\Dispatcher;

use Ecotone\Messaging\Channel\MessageDispatcher;
use Ecotone\Messaging\Channel\MessageDispatchingException;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHandler;

/**
 * Class BroadcastingDispatcher
 * @package Ecotone\Messaging\Channel\Dispatcher
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class BroadcastingDispatcher implements MessageDispatcher
{
    /**
     * @var MessageHandler[]
     */
    private $messageHandlers = [];

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