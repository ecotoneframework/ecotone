<?php

namespace SimplyCodedSoftware\Messaging\Endpoint;

use SimplyCodedSoftware\Messaging\MessageHandler;
use SimplyCodedSoftware\Messaging\PollableChannel;

/**
 * Class PollOrThrowPollableFactory
 * @package SimplyCodedSoftware\Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PollOrThrowPollableConsumerFactory implements PollableConsumerFactory
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