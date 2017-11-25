<?php

namespace Messaging\Endpoint;

use Messaging\MessageDeliveryException;
use Messaging\MessageHandler;
use Messaging\PollableChannel;

/**
 * Class PollingConsumer
 * @package Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PollOrThrowExceptionConsumer implements ConsumerLifecycle
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
    public function isMissingConfiguration(): bool
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
        if ($this->pollableChannel->receive()) {
            throw MessageDeliveryException::create("Message was not delivered to " . self::class);
        }

        $this->messageHandler->handle($this->pollableChannel->receive());
    }

    /**
     * @inheritDoc
     */
    public function isPollable(): bool
    {
        return true;
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
    public function getConsumerName(): string
    {
        return "single receive polling consumer";
    }
}