<?php

namespace SimplyCodedSoftware\Messaging\Channel;

use SimplyCodedSoftware\Messaging\Channel\Dispatcher\UnicastingDispatcher;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageHandler;
use SimplyCodedSoftware\Messaging\SubscribableChannel;

/**
 * Class DirectChannel
 * @package SimplyCodedSoftware\Messaging\Channel
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