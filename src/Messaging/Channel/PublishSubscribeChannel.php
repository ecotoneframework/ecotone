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
     * @var BroadcastingDispatcher
     */
    private $messageDispatcher;

    /**
     * PublishSubscribeChannel constructor.
     * @param BroadcastingDispatcher $messageDispatcher
     */
    public function __construct(BroadcastingDispatcher $messageDispatcher)
    {
        $this->messageDispatcher = $messageDispatcher;
    }

    /**
     * @return PublishSubscribeChannel
     */
    public static function create() : self
    {
        return new self(new BroadcastingDispatcher());
    }

    /**
     * @inheritDoc
     */
    public function send(Message $message): void
    {
        $this->messageDispatcher->dispatch($message);
    }

    /**
     * @inheritDoc
     */
    public function subscribe(MessageHandler $messageHandler): void
    {
        $this->messageDispatcher->addHandler($messageHandler);
    }

    /**
     * @inheritDoc
     */
    public function unsubscribe(MessageHandler $messageHandler): void
    {
        $this->messageDispatcher->removeHandler($messageHandler);
    }

    public function __toString()
    {
        return "publish subscribe channel";
    }
}