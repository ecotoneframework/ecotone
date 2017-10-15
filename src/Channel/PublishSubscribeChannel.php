<?php

namespace Messaging\Channel;

use Messaging\Channel\Dispatcher\BroadcastingDispatcher;
use Messaging\Message;
use Messaging\MessageHandler;
use Messaging\SubscribableChannel;

/**
 * Class PublishSubscribeChannel
 * @package Messaging\Channel
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
}