<?php

namespace Ecotone\Messaging\Channel\Dispatcher;

use Ecotone\Messaging\Channel\MessageDispatcher;
use Ecotone\Messaging\Channel\MessageDispatchingException;
use Ecotone\Messaging\Channel\WrongHandlerAmountException;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHandler;

/**
 * Class UnicastingDispatcher
 * @package Ecotone\Messaging\Dispatcher
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class UnicastingDispatcher implements MessageDispatcher
{
    /**
     * @var MessageHandler|null
     */
    private $messageHandler;

    public function __construct()
    {
    }

    public static function create() : self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function addHandler(MessageHandler $messageHandler): void
    {
        if ($this->messageHandler) {
            throw WrongHandlerAmountException::create("{$messageHandler} can't be registered as second handler for unicasting dispatcher. The first is {$this->messageHandler}");
        }

        $this->messageHandler = $messageHandler;
    }

    /**
     * @inheritDoc
     */
    public function removeHandler(MessageHandler $messageHandler): void
    {
        $this->messageHandler = null;
    }

    /**
     * @inheritDoc
     */
    public function dispatch(Message $message): void
    {
        if (!$this->messageHandler) {
            throw MessageDispatchingException::create("There is no message handler registered for dispatching Message {$message}");
        }

        $this->messageHandler->handle($message);
    }

    /**
     * @inheritDoc
     */
    public function getHandlerCount(): int
    {
        return $this->messageHandler ? 1 : 0;
    }
}