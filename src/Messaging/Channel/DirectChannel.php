<?php

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\Channel\Dispatcher\UnicastingDispatcher;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\SubscribableChannel;

/**
 * Class DirectChannel
 * @package Ecotone\Messaging\Channel
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
     * @return DirectChannel
     */
    public static function create() : self
    {
        return new self(new UnicastingDispatcher());
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
        return "direct channel";
    }
}