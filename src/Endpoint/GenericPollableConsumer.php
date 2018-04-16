<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Endpoint;

use SimplyCodedSoftware\IntegrationMessaging\PollableChannel;

/**
 * Class GenericPollableConsumer
 * @package SimplyCodedSoftware\IntegrationMessaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GenericPollableConsumer implements ConsumerLifecycle
{
    /**
     * @var bool
     */
    private $isRunning = false;
    /**
     * @var string
     */
    private $consumerName;
    /**
     * @var PollableChannel
     */
    private $inputChannel;
    /**
     * @var GenericPollableGateway
     */
    private $gateway;

    /**
     * GenericPollableConsumer constructor.
     * @param string $consumerName
     * @param PollableChannel $inputChannel
     * @param GenericPollableGateway $gateway
     */
    private function __construct(string $consumerName, PollableChannel $inputChannel, GenericPollableGateway $gateway)
    {
        $this->consumerName = $consumerName;
        $this->inputChannel = $inputChannel;
        $this->gateway = $gateway;
    }

    /**
     * @param string $consumerName
     * @param PollableChannel $inputChannel
     * @param GenericPollableGateway $gateway
     * @return GenericPollableConsumer
     */
    public static function createWith(string $consumerName, PollableChannel $inputChannel, GenericPollableGateway $gateway) : self
    {
        return new self($consumerName, $inputChannel, $gateway);
    }

    /**
     * @inheritDoc
     */
    public function start(): void
    {
        $this->isRunning = true;

        while ($this->isRunning) {
            $receivedMessage = $this->inputChannel->receive();

            if ($receivedMessage) {
                $this->gateway->runFlow($receivedMessage);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function stop(): void
    {
        $this->isRunning = false;
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
    public function getConsumerName(): string
    {
        return $this->consumerName;
    }
}