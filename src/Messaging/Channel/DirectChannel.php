<?php

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\SubscribableChannel;

/**
 * Class DirectChannel
 * @package Ecotone\Messaging\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DirectChannel implements SubscribableChannel
{
    private ?MessageHandler $messageHandler = null;

    /**
     * DirectChannel constructor.
     */
    private function __construct()
    {
    }

    /**
     * @return DirectChannel
     */
    public static function create() : self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function send(Message $message): void
    {
        if (!$this->messageHandler) {
            throw MessageDispatchingException::create("There is no message handler registered for dispatching Message {$message}");
        }

        $this->messageHandler->handle($message);
    }

    /**
     * @inheritDoc
     */
    public function subscribe(MessageHandler $messageHandler): void
    {
        if ($this->messageHandler) {
            throw WrongHandlerAmountException::create("{$messageHandler} can't be registered as second handler for unicasting dispatcher. The first is {$this->messageHandler}");
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
        return "direct channel";
    }
}