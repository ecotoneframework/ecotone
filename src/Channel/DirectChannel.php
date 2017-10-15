<?php

namespace Messaging\Channel;

use Messaging\Channel\Dispatcher\UnicastingDispatcher;
use Messaging\Message;
use Messaging\MessageChannel;
use Messaging\MessageHandler;
use Messaging\SubscribableChannel;

/**
 * Class DirectChannel
 * @package Messaging\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DirectChannel implements SubscribableChannel
{
    /**
     * @var UnicastingDispatcher
     */
    private $messageDispatcher;

    /**
     * DirectChannel constructor.
     * @param UnicastingDispatcher $messageDispatcher
     */
    public function __construct(UnicastingDispatcher $messageDispatcher)
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