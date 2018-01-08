<?php

namespace SimplyCodedSoftware\Messaging\Endpoint;
use SimplyCodedSoftware\Messaging\MessageHandler;
use SimplyCodedSoftware\Messaging\PollableChannel;

/**
 * Interface PollableFactory
 * @package SimplyCodedSoftware\Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface PollableConsumerFactory
{
    /**
     * @param string $consumerName
     * @param PollableChannel $pollableChannel
     * @param MessageHandler $messageHandler
     * @return ConsumerLifecycle
     */
    public function create(string $consumerName, PollableChannel $pollableChannel, MessageHandler $messageHandler) : ConsumerLifecycle;
}