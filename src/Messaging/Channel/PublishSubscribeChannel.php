<?php

namespace SimplyCodedSoftware\Messaging\Channel;

use SimplyCodedSoftware\Messaging\Channel\Dispatcher\BroadcastingDispatcher;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageHandler;
use SimplyCodedSoftware\Messaging\SubscribableChannel;

/**
 * Class PublishSubscribeChannel
 * @package SimplyCodedSoftware\Messaging\Channel
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