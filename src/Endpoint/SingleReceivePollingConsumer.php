<?php

namespace Messaging\Endpoint;

use Messaging\MessageHandler;
use Messaging\PollableChannel;

/**
 * Class PollingConsumer
 * @package Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SingleReceivePollingConsumer implements ConsumerLifecycle
{
    /**
     * @var PollableChannel
     */
    private $pollableChannel;
    /**
     * @var MessageHandler
     */
    private $messageHandler;

    /**
     * PollingConsumer constructor.
     * @param PollableChannel $pollableChannel
     * @param MessageHandler $messageHandler
     */
    public function __construct(PollableChannel $pollableChannel, MessageHandler $messageHandler)
    {
        $this->pollableChannel = $pollableChannel;
        $this->messageHandler = $messageHandler;
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
        return "";
    }

    /**
     * @inheritDoc
     */
    public function start(): void
    {
        $this->messageHandler->handle($this->pollableChannel->receive());
    }

    /**
     * @inheritDoc
     */
    public function stop(): void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function isRunning(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getComponentName(): string
    {
        return "single receive polling consumer";
    }
}