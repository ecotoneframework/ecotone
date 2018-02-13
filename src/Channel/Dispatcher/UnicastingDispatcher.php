<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Channel\Dispatcher;

use SimplyCodedSoftware\IntegrationMessaging\Channel\MessageDispatcher;
use SimplyCodedSoftware\IntegrationMessaging\Channel\MessageDispatchingException;
use SimplyCodedSoftware\IntegrationMessaging\Channel\WrongHandlerAmountException;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;

/**
 * Class UnicastingDispatcher
 * @package SimplyCodedSoftware\IntegrationMessaging\Dispatcher
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
            throw WrongHandlerAmountException::create("Message handler " . get_class($messageHandler) . " can't be registered as second handler for unicasting dispatcher");
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
            throw MessageDispatchingException::create("There is no message handler registered for dispatching");
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