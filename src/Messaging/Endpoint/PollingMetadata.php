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
    const DEFAULT_MEMORY_LIMIT_MEGABYTES = 0;
    const DEFAULT_HANDLED_MESSAGE_LIMIT = 0;

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
    private $handledMessageLimit = self::DEFAULT_HANDLED_MESSAGE_LIMIT;
    /**
     * @var int
     */
    private $memoryLimitInMegabytes = self::DEFAULT_MEMORY_LIMIT_MEGABYTES;
    /**
     * @var int
     */
    private $maxMessagePerPoll = self::DEFAULT_MAX_MESSAGES_PER_POLL;
    /**
     * @var bool
     */
    private $withSignalInterceptors = false;
    /**
     * @var string
     */
    private $triggerReferenceName = "";
    /**
     * @var string
     */
    private $taskExecutorName = "";

    /**
     * PollingMetadata constructor.
     * @param string $endpointId
     */
    private function __construct(string $endpointId)
    {
        $this->endpointId = $endpointId;
        $this->withSignalInterceptors = extension_loaded('pcntl');
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
     * @param PollingMetadata $pollingMetadata
     * @return PollingMetadata
     */
    public static function createFrom(PollingMetadata $pollingMetadata) : self
    {
        return clone $pollingMetadata;
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
     * @param int $handledMessageLimit
     * @return PollingMetadata
     */
    public function setHandledMessageLimit(int $handledMessageLimit): PollingMetadata
    {
        $copy = $this->createCopy();
        $copy->handledMessageLimit = $handledMessageLimit;

        return $copy;
    }

    /**
     * @param int $memoryLimit
     * @return PollingMetadata
     */
    public function setMemoryLimitInMegaBytes(int $memoryLimit) : PollingMetadata
    {
        $copy = $this->createCopy();
        $copy->memoryLimitInMegabytes = $memoryLimit;

        return $copy;
    }

    /**
     * @param bool $withSignalInterceptors
     * @return PollingMetadata
     */
    public function setSignalInterceptors(bool $withSignalInterceptors) : PollingMetadata
    {
        $copy = $this->createCopy();
        $copy->withSignalInterceptors = $withSignalInterceptors;

        return $copy;
    }


    /**
     * @return bool
     */
    public function isWithSignalInterceptors(): bool
    {
        return $this->withSignalInterceptors;
    }

    /**
     * @return int
     */
    public function getMemoryLimitInMegabytes(): int
    {
        return $this->memoryLimitInMegabytes;
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
    public function getHandledMessageLimit(): int
    {
        return $this->handledMessageLimit;
    }

    /**
     * @return PollingMetadata
     */
    private function createCopy() : self
    {
        return clone $this;
    }
}