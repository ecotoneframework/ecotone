<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Endpoint;

/**
 * Class PollingMetadata
 * @package SimplyCodedSoftware\IntegrationMessaging\Endpoint\PollingConsumer
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PollingMetadata
{
    private const DEFAULT_MAX_MESSAGES_PER_POLL = 1;

    private const DEFAULT_FIXED_RATE = 1000;

    private const DEFAULT_INITIAL_DELAY = 0;

    /**
     * @var string
     */
    private $messageHandlerName;
    /**
     * @var string
     */
    private $cron = "";
    /**
     * @var string
     */
    private $errorChannelName = "";
    /**
     * @var int
     */
    private $maxMessagePerPoll = self::DEFAULT_MAX_MESSAGES_PER_POLL;
    /**
     * @var string
     */
    private $triggerReferenceName = "";
    /**
     * @var string
     */
    private $taskExecutorName = "";
    /**
     * @var int in milliseconds
     */
    private $fixedRateInMilliseconds = self::DEFAULT_FIXED_RATE;
    /**
     * @var int in milliseconds
     */
    private $initialDelayInMilliseconds = self::DEFAULT_INITIAL_DELAY;
    /**
     * @var string[]
     */
    private $transactionFactoryReferenceNames = [];

    /**
     * PollingMetadata constructor.
     * @param string $messageHandlerName
     */
    private function __construct(string $messageHandlerName)
    {
        $this->messageHandlerName = $messageHandlerName;
    }

    /**
     * @param string $messageHandlerName
     * @return PollingMetadata
     */
    public static function create(string $messageHandlerName) : self
    {
        return new self($messageHandlerName);
    }

    /**
     * @param string $cron
     * @return PollingMetadata
     */
    public function setCron(string $cron): PollingMetadata
    {
        $copy = $this->createCopy();
        $copy->cron = $cron;

        return $copy;
    }

    /**
     * @param string $errorChannelName
     * @return PollingMetadata
     */
    public function setErrorChannelName(string $errorChannelName): PollingMetadata
    {
        $copy = $this->createCopy();
        $copy->errorChannelName = $errorChannelName;

        return $copy;
    }

    /**
     * @param int $maxMessagePerPoll
     * @return PollingMetadata
     */
    public function setMaxMessagePerPoll(int $maxMessagePerPoll): PollingMetadata
    {
        $copy = $this->createCopy();
        $copy->maxMessagePerPoll = $maxMessagePerPoll;

        return $copy;
    }

    /**
     * @param string $triggerReferenceName
     * @return PollingMetadata
     */
    public function setTriggerReferenceName(string $triggerReferenceName): PollingMetadata
    {
        $copy = $this->createCopy();
        $copy->triggerReferenceName = $triggerReferenceName;

        return $copy;
    }

    /**
     * @param string $taskExecutorName
     * @return PollingMetadata
     */
    public function setTaskExecutorName(string $taskExecutorName): PollingMetadata
    {
        $copy = $this->createCopy();
        $copy->taskExecutorName = $taskExecutorName;

        return $copy;
    }

    /**
     * @param int $fixedRateInMilliseconds
     * @return PollingMetadata
     */
    public function setFixedRateInMilliseconds(int $fixedRateInMilliseconds): PollingMetadata
    {
        $copy = $this->createCopy();
        $copy->fixedRateInMilliseconds = $fixedRateInMilliseconds;

        return $copy;
    }

    /**
     * @param int $initialDelayInMilliseconds
     * @return PollingMetadata
     */
    public function setInitialDelayInMilliseconds(int $initialDelayInMilliseconds): PollingMetadata
    {
        $copy = $this->createCopy();
        $copy->initialDelayInMilliseconds = $initialDelayInMilliseconds;

        return $copy;
    }

    /**
     * @param string[] $transactionFactoryReferenceNames
     * @return PollingMetadata
     */
    public function setTransactionFactoryReferenceNames(array $transactionFactoryReferenceNames): PollingMetadata
    {
        $copy = $this->createCopy();
        $copy->transactionFactoryReferenceNames = $transactionFactoryReferenceNames;

        return $copy;
    }

    /**
     * @return string[]
     */
    public function getTransactionFactoryReferenceNames(): array
    {
        return $this->transactionFactoryReferenceNames;
    }

    /**
     * @return string
     */
    public function getCron(): string
    {
        return $this->cron;
    }

    /**
     * @return string
     */
    public function getErrorChannelName(): string
    {
        return $this->errorChannelName;
    }

    /**
     * @return int
     */
    public function getMaxMessagePerPoll(): int
    {
        return $this->maxMessagePerPoll;
    }

    /**
     * @return string
     */
    public function getTriggerReferenceName(): string
    {
        return $this->triggerReferenceName;
    }

    /**
     * @return string
     */
    public function getTaskExecutorName(): string
    {
        return $this->taskExecutorName;
    }

    /**
     * @return int
     */
    public function getFixedRateInMilliseconds(): int
    {
        return $this->fixedRateInMilliseconds;
    }

    /**
     * @return int
     */
    public function getInitialDelayInMilliseconds(): int
    {
        return $this->initialDelayInMilliseconds;
    }

    /**
     * @return string
     */
    public function getMessageHandlerName(): string
    {
        return $this->messageHandlerName;
    }

    /**
     * @return PollingMetadata
     */
    private function createCopy() : self
    {
        return clone $this;
    }
}