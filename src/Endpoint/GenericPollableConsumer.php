<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Endpoint;
use SimplyCodedSoftware\IntegrationMessaging\MessageChannel;
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
     * @var MessageChannel
     */
    private $outputChannel;

    /**
     * GenericPollableConsumer constructor.
     * @param string $consumerName
     * @param PollableChannel $inputChannel
     * @param MessageChannel $outputChannel
     */
    private function __construct(string $consumerName, PollableChannel $inputChannel, MessageChannel $outputChannel)
    {
        $this->consumerName = $consumerName;
        $this->inputChannel = $inputChannel;
        $this->outputChannel = $outputChannel;
    }

    /**
     * @param string $consumerName
     * @param PollableChannel $inputChannel
     * @param MessageChannel $outputChannel
     * @return GenericPollableConsumer
     */
    public static function createWith(string $consumerName, PollableChannel $inputChannel, MessageChannel $outputChannel) : self
    {
        return new self($consumerName, $inputChannel, $outputChannel);
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
                $this->outputChannel->send($receivedMessage);
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