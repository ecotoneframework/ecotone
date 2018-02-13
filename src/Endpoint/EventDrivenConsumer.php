<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Endpoint;

use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\SubscribableChannel;

/**
 * Class EventDrivenConsumer
 * @package SimplyCodedSoftware\IntegrationMessaging\Endpoint
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
    }

    public function start(): void
    {
        $this->subscribableChannel->subscribe($this->messageHandler);
    }

    public function stop(): void
    {
        $this->subscribableChannel->unsubscribe($this->messageHandler);
    }

    /**
     * @inheritDoc
     */
    public function isRunningInSeparateThread(): bool
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
}