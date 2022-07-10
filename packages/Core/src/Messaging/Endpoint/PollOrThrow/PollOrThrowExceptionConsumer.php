<?php

namespace Ecotone\Messaging\Endpoint\PollOrThrow;

use Ecotone\Messaging\Endpoint\ConsumerLifecycle;
use Ecotone\Messaging\MessageDeliveryException;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\PollableChannel;

/**
 * Class PollingConsumer
 * @package Ecotone\Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PollOrThrowExceptionConsumer implements ConsumerLifecycle
{
    private string $consumerName;
    private \Ecotone\Messaging\PollableChannel $pollableChannel;
    private \Ecotone\Messaging\MessageHandler $messageHandler;

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

    /**
     * @param PollableChannel $pollableChannel
     * @param MessageHandler $messageHandler
     * @return PollOrThrowExceptionConsumer
     */
    public static function createWithoutName(PollableChannel $pollableChannel, MessageHandler $messageHandler) : self
    {
        return new self("some random name", $pollableChannel, $messageHandler);
    }

    /**
     * @param string $consumerName
     * @param PollableChannel $pollableChannel
     * @param MessageHandler $messageHandler
     * @return PollOrThrowExceptionConsumer
     */
    public static function create(string $consumerName, PollableChannel $pollableChannel, MessageHandler $messageHandler) : self
    {
        return new self($consumerName, $pollableChannel, $messageHandler);
    }

    /**
     * @inheritDoc
     */
    public function run(): void
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
    public function isRunningInSeparateThread(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function stop(): void
    {
    }

    /**
     * @inheritDoc
     */
    public function getConsumerName(): string
    {
        return $this->consumerName;
    }
}