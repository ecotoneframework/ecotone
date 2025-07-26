<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplateBuilder;

/**
 * licence Apache-2.0
 */
final class PollingMetadata implements DefinedObject
{
    public const DEFAULT_MAX_MESSAGES_PER_POLL = 1;

    public const DEFAULT_FIXED_RATE = 1000;

    public const DEFAULT_INITIAL_DELAY = 0;
    public const DEFAULT_MEMORY_LIMIT_MEGABYTES = 1024;
    public const DEFAULT_HANDLED_MESSAGE_LIMIT = 0;
    public const DEFAULT_EXECUTION_LIMIT = 0;
    public const DEFAULT_EXECUTION_TIME_LIMIT_IN_MILLISECONDS = 0;
    public const DEFAULT_STOP_ON_ERROR = false;
    public const DEFAULT_FINISH_WHEN_NO_MESSAGES = false;

    private bool $withSignalInterceptors;


    /**
     * PollingMetadata constructor.
     * @param string $endpointId
     */
    public function __construct(
        private string $endpointId,
        private string $cron = '',
        private string $errorChannelName = '',
        private bool $isErrorChannelEnabled = true,
        private int $fixedRateInMilliseconds = self::DEFAULT_FIXED_RATE,
        private int $initialDelayInMilliseconds = self::DEFAULT_INITIAL_DELAY,
        private int $handledMessageLimit = self::DEFAULT_HANDLED_MESSAGE_LIMIT,
        private int $memoryLimitInMegabytes = self::DEFAULT_MEMORY_LIMIT_MEGABYTES,
        private int $executionAmountLimit = self::DEFAULT_EXECUTION_LIMIT,
        private int $maxMessagePerPoll = self::DEFAULT_MAX_MESSAGES_PER_POLL,
        private int $executionTimeLimitInMilliseconds = self::DEFAULT_EXECUTION_TIME_LIMIT_IN_MILLISECONDS,
        private ?RetryTemplateBuilder $connectionRetryTemplate = null,
        ?bool $withSignalInterceptors = null,
        private string $triggerReferenceName = '',
        private string $taskExecutorName = '',
        private bool $stopOnError = self::DEFAULT_STOP_ON_ERROR,
        private bool $finishWhenNoMessages = self::DEFAULT_FINISH_WHEN_NO_MESSAGES,
        private ?string $fixedRateExpression = null,
        private ?string $cronExpression = null,
    ) {
        $this->withSignalInterceptors = $withSignalInterceptors ?? extension_loaded('pcntl');
    }

    /**
     * @param string $endpointId
     * @return PollingMetadata
     */
    public static function create(string $endpointId): self
    {
        return new self($endpointId);
    }

    /**
     * @param int $amountOfMessagesToHandle how many messages should this consumer handle before exiting
     * @param int $maxExecutionTimeInMilliseconds Maximum execution of running consumer. Take under that while debugging with xdebug it should be set to 0 to avoid exiting consumer to early.
     * @return $this
     */
    public function withTestingSetup(int $amountOfMessagesToHandle = 1, int $maxExecutionTimeInMilliseconds = 100, bool $failAtError = true): self
    {
        $pollingMetadata = $this
            ->setHandledMessageLimit($amountOfMessagesToHandle)
            ->setStopOnError($failAtError);

        if ($maxExecutionTimeInMilliseconds) {
            $pollingMetadata = $pollingMetadata
                ->setExecutionTimeLimitInMilliseconds($maxExecutionTimeInMilliseconds);
        }

        return $pollingMetadata;
    }

    /**
     * @param PollingMetadata $pollingMetadata
     * @return PollingMetadata
     */
    public static function createFrom(PollingMetadata $pollingMetadata): self
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

    public function applyExecutionPollingMetadata(?ExecutionPollingMetadata $executionPollingMetadata): self
    {
        if (! $executionPollingMetadata) {
            return $this;
        }

        $copy = $this->createCopy();

        if (! is_null($executionPollingMetadata->getStopOnError())) {
            $copy = $copy->setStopOnError($executionPollingMetadata->getStopOnError());
        }
        if (! is_null($executionPollingMetadata->getHandledMessageLimit())) {
            $copy = $copy->setHandledMessageLimit($executionPollingMetadata->getHandledMessageLimit());
        }
        if (! is_null($executionPollingMetadata->getExecutionTimeLimitInMilliseconds())) {
            $copy = $copy->setExecutionTimeLimitInMilliseconds($executionPollingMetadata->getExecutionTimeLimitInMilliseconds());
        }
        if (! is_null($executionPollingMetadata->getMemoryLimitInMegabytes())) {
            $copy = $copy->setMemoryLimitInMegaBytes($executionPollingMetadata->getMemoryLimitInMegabytes());
        }
        if (! is_null($executionPollingMetadata->getCron())) {
            $copy = $copy->setCron($executionPollingMetadata->getCron());
        }
        if (! is_null($executionPollingMetadata->getFinishWhenNoMessages())) {
            $copy = $copy->setFinishWhenNoMessages($executionPollingMetadata->getFinishWhenNoMessages());
        }
        if (! is_null($executionPollingMetadata->getExecutionAmountLimit())) {
            $copy = $copy->setExecutionAmountLimit($executionPollingMetadata->getExecutionAmountLimit());
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

    public function setEnabledErrorChannel(bool $isErrorChannelEnabled): PollingMetadata
    {
        $copy = $this->createCopy();
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

    public function setConnectionRetryTemplate(RetryTemplateBuilder $retryTemplateBuilder): PollingMetadata
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
     * @return int return 0, if no timeout given
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
    public function setMemoryLimitInMegaBytes(int $memoryLimit): PollingMetadata
    {
        $copy = $this->createCopy();
        $copy->memoryLimitInMegabytes = $memoryLimit;

        return $copy;
    }

    /**
     * @param bool $withSignalInterceptors
     * @return PollingMetadata
     */
    public function setSignalInterceptors(bool $withSignalInterceptors): PollingMetadata
    {
        $copy = $this->createCopy();
        $copy->withSignalInterceptors = $withSignalInterceptors;

        return $copy;
    }

    /**
     * How many times Consumer should be executed before it will be stopped.
     * It takes under consideration polling when no messages are received.
     *
     * @param int $executionAmountLimit
     * @return PollingMetadata
     */
    public function setExecutionAmountLimit(int $executionAmountLimit): PollingMetadata
    {
        $copy = $this->createCopy();
        $copy->executionAmountLimit = $executionAmountLimit;

        return $copy;
    }

    public function setFinishWhenNoMessages(bool $finishWhenNoMessages): PollingMetadata
    {
        $copy = $this->createCopy();
        $copy->finishWhenNoMessages = $finishWhenNoMessages;
        $copy->handledMessageLimit = 0;
        $copy->executionAmountLimit = 0;
        $copy->executionTimeLimitInMilliseconds = 0;

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
        return $this->stopOnError ? '' : $this->errorChannelName;
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

    public function finishWhenNoMessages(): bool
    {
        return $this->finishWhenNoMessages;
    }

    public function getFixedRateExpression(): ?string
    {
        return $this->fixedRateExpression;
    }

    public function getCronExpression(): ?string
    {
        return $this->cronExpression;
    }

    public function hasFixedRateExpression(): bool
    {
        return $this->fixedRateExpression !== null;
    }

    public function hasCronExpression(): bool
    {
        return $this->cronExpression !== null;
    }

    public function setFixedRateExpression(?string $fixedRateExpression): PollingMetadata
    {
        $copy = $this->createCopy();
        $copy->fixedRateExpression = $fixedRateExpression;

        return $copy;
    }

    public function setCronExpression(?string $cronExpression): PollingMetadata
    {
        $copy = $this->createCopy();
        $copy->cronExpression = $cronExpression;

        return $copy;
    }

    /**
     * @return PollingMetadata
     */
    private function createCopy(): self
    {
        return clone $this;
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [
            $this->endpointId,
            $this->cron,
            $this->errorChannelName,
            $this->isErrorChannelEnabled,
            $this->fixedRateInMilliseconds,
            $this->initialDelayInMilliseconds,
            $this->handledMessageLimit,
            $this->memoryLimitInMegabytes,
            $this->executionAmountLimit,
            $this->maxMessagePerPoll,
            $this->executionTimeLimitInMilliseconds,
            $this->connectionRetryTemplate,
            $this->withSignalInterceptors,
            $this->triggerReferenceName,
            $this->taskExecutorName,
            $this->stopOnError,
            $this->finishWhenNoMessages,
            $this->fixedRateExpression,
            $this->cronExpression,
        ]);
    }
}
