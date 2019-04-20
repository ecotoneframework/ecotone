<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Endpoint;

/**
 * Class PollingMetadata
 * @package SimplyCodedSoftware\Messaging\Endpoint\PollingConsumer
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PollingMetadata
{
    const DEFAULT_MAX_MESSAGES_PER_POLL = 1;

    const DEFAULT_FIXED_RATE = 1000;

    const DEFAULT_INITIAL_DELAY = 0;

    /**
     * @var string
     */
    private $endpointId;
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
     * @var int
     */
    private $stopAfterExceedingHandledMessageLimit = 0;
    /**
     * @var string[]
     */
    private $transactionFactoryReferenceNames = [];

    /**
     * PollingMetadata constructor.
     * @param string $endpointId
     */
    private function __construct(string $endpointId)
    {
        $this->endpointId = $endpointId;
    }

    /**
     * @param string $endpointId
     * @return PollingMetadata
     */
    public static function create(string $endpointId) : self
    {
        return new self($endpointId);
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
     * @param int $stopAfterExceedingHandledMessageLimit
     * @return PollingMetadata
     */
    public function setStopAfterExceedingHandledMessageLimit(int $stopAfterExceedingHandledMessageLimit): PollingMetadata
    {
        $copy = $this->createCopy();
        $copy->stopAfterExceedingHandledMessageLimit = $stopAfterExceedingHandledMessageLimit;

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
    public function getEndpointId(): string
    {
        return $this->endpointId;
    }

    /**
     * @return int
     */
    public function getStopAfterExceedingHandledMessageLimit(): int
    {
        return $this->stopAfterExceedingHandledMessageLimit;
    }

    /**
     * @return PollingMetadata
     */
    private function createCopy() : self
    {
        return clone $this;
    }
}