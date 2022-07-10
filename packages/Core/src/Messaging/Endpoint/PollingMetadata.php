<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint;

use Ecotone\Messaging\Handler\Recoverability\RetryTemplateBuilder;

final class PollingMetadata
{
    const DEFAULT_MAX_MESSAGES_PER_POLL = 1;

    const DEFAULT_FIXED_RATE = 1000;

    const DEFAULT_INITIAL_DELAY = 0;
    const DEFAULT_MEMORY_LIMIT_MEGABYTES = 1024;
    const DEFAULT_HANDLED_MESSAGE_LIMIT = 0;
    const DEFAULT_EXECUTION_LIMIT = 0;
    const DEFAULT_EXECUTION_TIME_LIMIT_IN_MILLISECONDS = 0;
    const DEFAULT_STOP_ON_ERROR = false;

    private string $endpointId;
    private string $cron = "";
    private string $errorChannelName = "";
    private bool $isErrorChannelEnabled = true;
    /**
     * @var int in milliseconds
     */
    private int $fixedRateInMilliseconds = self::DEFAULT_FIXED_RATE;
    /**
     * @var int in milliseconds
     */
    private int $initialDelayInMilliseconds = self::DEFAULT_INITIAL_DELAY;
    private int $handledMessageLimit = self::DEFAULT_HANDLED_MESSAGE_LIMIT;
    private int $memoryLimitInMegabytes = self::DEFAULT_MEMORY_LIMIT_MEGABYTES;
    private int $executionAmountLimit = self::DEFAULT_EXECUTION_LIMIT;
    private int $maxMessagePerPoll = self::DEFAULT_MAX_MESSAGES_PER_POLL;
    private int $executionTimeLimitInMilliseconds = self::DEFAULT_EXECUTION_TIME_LIMIT_IN_MILLISECONDS;
    private ?\Ecotone\Messaging\Handler\Recoverability\RetryTemplateBuilder $connectionRetryTemplate = null;
    private bool $withSignalInterceptors = false;
    private string $triggerReferenceName = "";
    private string $taskExecutorName = "";
    private bool $stopOnError = self::DEFAULT_STOP_ON_ERROR;

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

    public function withTestingSetup(): self
    {
        return $this
            ->setExecutionAmountLimit(1)
            ->setExecutionTimeLimitInMilliseconds(1)
            ->setStopOnError(true);
    }

    /**
     * @param PollingMetadata $pollingMetadata
     * @return PollingMetadata
     */
    public static function createFrom(PollingMetadata $pollingMetadata) : self
    {
        return clone $pollingMetadata;
    }

    public function setStopOnError(bool $stopOnError): self
    {
        $copy = $this->createCopy();
        $copy->stopOnError = $stopOnError;

        return $copy;
    }

    public function isStoppedOnError(): bool
    {
        return $this->stopOnError;
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

    public function applyExecutionPollingMetadata(?ExecutionPollingMetadata $executionPollingMetadata) : self
    {
        if (!$executionPollingMetadata) {
            return $this;
        }

        $copy = $this->createCopy();

        if (!is_null($executionPollingMetadata->getStopOnError())) {
            $copy = $copy->setStopOnError($executionPollingMetadata->getStopOnError());
        }
        if (!is_null($executionPollingMetadata->getHandledMessageLimit())) {
            $copy = $copy->setHandledMessageLimit($executionPollingMetadata->getHandledMessageLimit());
        }
        if (!is_null($executionPollingMetadata->getExecutionTimeLimitInMilliseconds())) {
            $copy = $copy->setExecutionTimeLimitInMilliseconds($executionPollingMetadata->getExecutionTimeLimitInMilliseconds());
        }
        if (!is_null($executionPollingMetadata->getMemoryLimitInMegabytes())) {
            $copy = $copy->setMemoryLimitInMegaBytes($executionPollingMetadata->getMemoryLimitInMegabytes());
        }
        if (!is_null($executionPollingMetadata->getCron())) {
            $copy = $copy->setCron($executionPollingMetadata->getCron());
        }

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

    public function setEnabledErrorChannel(bool $isErrorChannelEnabled) : PollingMetadata
    {
        $copy                        = $this->createCopy();
        $copy->isErrorChannelEnabled = $isErrorChannelEnabled;

        return $copy;
    }

    public function isErrorChannelEnabled(): bool
    {
        return $this->isErrorChannelEnabled;
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

    public function setConnectionRetryTemplate(RetryTemplateBuilder $retryTemplateBuilder) : PollingMetadata
    {
        $copy = $this->createCopy();
        $copy->connectionRetryTemplate = $retryTemplateBuilder;

        return $copy;
    }

    public function getConnectionRetryTemplate(): ?RetryTemplateBuilder
    {
        return $this->connectionRetryTemplate;
    }

    /**
     * @return int
     */
    public function getExecutionTimeLimitInMilliseconds(): int
    {
        return $this->executionTimeLimitInMilliseconds;
    }

    /**
     * @param int $executionTimeLimitInMilliseconds
     * @return PollingMetadata
     */
    public function setExecutionTimeLimitInMilliseconds(int $executionTimeLimitInMilliseconds): PollingMetadata
    {
        $copy = $this->createCopy();
        $copy->executionTimeLimitInMilliseconds = $executionTimeLimitInMilliseconds;

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
     * @param int $executionAmountLimit
     * @return PollingMetadata
     */
    public function setExecutionAmountLimit(int $executionAmountLimit): PollingMetadata
    {
        $copy = $this->createCopy();
        $copy->executionAmountLimit = $executionAmountLimit;

        return $copy;
    }

    /**1
     * @return int
     */
    public function getExecutionAmountLimit(): int
    {
        return $this->executionAmountLimit;
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
        return $this->stopOnError ? "" : $this->errorChannelName;
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