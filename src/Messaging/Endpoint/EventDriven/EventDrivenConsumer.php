<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\EventDriven;

use Ecotone\Messaging\Endpoint\ConsumerLifecycle;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\SubscribableChannel;

/**
 * Class EventDrivenConsumer
 * @package Ecotone\Messaging\Endpoint
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

    public function run(): void
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