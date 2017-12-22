<?php

namespace Messaging\Endpoint;

use Messaging\MessageHandler;
use Messaging\PollableChannel;

/**
 * Class PollOrThrowPollableFactory
 * @package Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PollOrThrowPollableFactory implements PollableFactory
{
    public function __construct()
    {
    }

    /**
     * @inheritDoc
     */
    public function create(string $consumerName, PollableChannel $pollableChannel, MessageHandler $messageHandler): ConsumerLifecycle
    {
        return PollOrThrowExceptionConsumer::create($consumerName, $pollableChannel, $messageHandler);
    }
}