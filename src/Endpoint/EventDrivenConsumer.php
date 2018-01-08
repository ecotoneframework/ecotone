<?php

namespace SimplyCodedSoftware\Messaging\Endpoint;

use SimplyCodedSoftware\Messaging\MessageHandler;
use SimplyCodedSoftware\Messaging\SubscribableChannel;

/**
 * Class EventDrivenConsumer
 * @package SimplyCodedSoftware\Messaging\Endpoint
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
     * @var string
     */
    private $consumerName;

    /**
     * EventDrivenConsumer constructor.
     * @param string $consumerName
     * @param SubscribableChannel $subscribableChannel
     * @param MessageHandler $messageHandler
     */
    public function __construct(string $consumerName, SubscribableChannel $subscribableChannel, MessageHandler $messageHandler)
    {
        $this->consumerName = $consumerName;
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
    public function isMissingConfiguration(): bool
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
    public function isPollable(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getConsumerName(): string
    {
        return $this->consumerName;
    }

    private function initialize() : void
    {
        $this->isRunning = false;
    }
}