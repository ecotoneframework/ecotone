<?php

namespace Messaging\Endpoint;

use Messaging\MessageHandler;
use Messaging\SubscribableChannel;

/**
 * Class EventDrivenConsumer
 * @package Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EventDrivenConsumer implements ConsumerLifecycle
{
    /**
     * @var SubscribableChannel
     */
    private $subscribableChannel;
    /**
     * @var MessageHandler
     */
    private $messageHandler;
    /**
     * @var bool
     */
    private $isRunning;

    /**
     * EventDrivenConsumer constructor.
     * @param SubscribableChannel $subscribableChannel
     * @param MessageHandler $messageHandler
     */
    public function __construct(SubscribableChannel $subscribableChannel, MessageHandler $messageHandler)
    {
        $this->subscribableChannel = $subscribableChannel;
        $this->messageHandler = $messageHandler;

        $this->initialize();
    }

    public function start(): void
    {
        $this->subscribableChannel->subscribe($this->messageHandler);
        $this->isRunning = true;
    }

    public function stop(): void
    {
        $this->subscribableChannel->unsubscribe($this->messageHandler);
        $this->isRunning = false;
    }

    public function isRunning(): bool
    {
        return $this->isRunning;
    }

    /**
     * @inheritDoc
     */
    public function canBeRun(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getMissingConfiguration(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getComponentName(): string
    {
        return "Event Driven Consumer";
    }

    private function initialize() : void
    {
        $this->isRunning = false;
    }
}