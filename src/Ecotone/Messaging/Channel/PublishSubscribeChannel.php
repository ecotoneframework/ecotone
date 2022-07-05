<?php

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\Channel\Dispatcher\BroadcastingDispatcher;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\SubscribableChannel;

/**
 * Class PublishSubscribeChannel
 * @package Ecotone\Messaging\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PublishSubscribeChannel implements SubscribableChannel
{
    /**
     * @var MessageHandler[]
     */
    private array $messageHandlers = [];

    private function __construct()
    {
    }

    /**
     * @return PublishSubscribeChannel
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
        return "publish subscribe channel";
    }
}