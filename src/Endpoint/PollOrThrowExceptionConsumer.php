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
     * @var string
     */
    private $consumerName;
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
     * @param string $consumerName
     * @param PollableChannel $pollableChannel
     * @param MessageHandler $messageHandler
     */
    public function __construct(string $consumerName, PollableChannel $pollableChannel, MessageHandler $messageHandler)
    {
        $this->consumerName = $consumerName;
        $this->pollableChannel = $pollableChannel;
        $this->messageHandler = $messageHandler;
    }

    public static function createWithoutName(PollableChannel $pollableChannel, MessageHandler $messageHandler) : self
    {
        return new self("some random name", $pollableChannel, $messageHandler);
    }

    public static function create(string $consumerName, PollableChannel $pollableChannel, MessageHandler $messageHandler) : self
    {
        return new self($consumerName, $pollableChannel, $messageHandler);
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
        $message = $this->pollableChannel->receive();
        if (is_null($message)) {
            throw MessageDeliveryException::create("Message was not delivered to " . self::class);
        }

        $this->messageHandler->handle($message);
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
        return $this->consumerName;
    }
}