<?php

namespace Messaging\Endpoint;

use Messaging\MessageHandler;
use Messaging\PollableChannel;

/**
 * Class PollingConsumer
 * @package Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PollingConsumer implements ConsumerLifecycle
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
    public function start(): void
    {

    }

    /**
     * @inheritDoc
     */
    public function stop(): void
    {
        // TODO: Implement stop() method.
    }

    /**
     * @inheritDoc
     */
    public function isRunning(): bool
    {
        // TODO: Implement isRunning() method.
    }

    /**
     * @inheritDoc
     */
    public function canBeRun(): bool
    {
        // TODO: Implement canBeRun() method.
    }

    /**
     * @inheritDoc
     */
    public function getMissingConfiguration(): string
    {
        // TODO: Implement getMissingConfiguration() method.
    }

    /**
     * @inheritDoc
     */
    public function getComponentName(): string
    {
        // TODO: Implement getComponentName() method.
    }
}