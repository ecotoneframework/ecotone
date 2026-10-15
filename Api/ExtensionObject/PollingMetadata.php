<?php

declare(strict_types=1);

namespace Ecotone\Api\ExtensionObject;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;

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

    public readonly bool $withSignalInterceptors;

    public function __construct(
        private readonly string $endpointId,
        public readonly string $cron = '',
        private readonly string $errorChannelName = '',
        public readonly bool $isErrorChannelEnabled = true,
        public readonly int $fixedRateInMilliseconds = self::DEFAULT_FIXED_RATE,
        public readonly int $initialDelayInMilliseconds = self::DEFAULT_INITIAL_DELAY,
        public readonly int $handledMessageLimit = self::DEFAULT_HANDLED_MESSAGE_LIMIT,
        public readonly int $memoryLimitInMegabytes = self::DEFAULT_MEMORY_LIMIT_MEGABYTES,
        public readonly int $executionAmountLimit = self::DEFAULT_EXECUTION_LIMIT,
        public readonly int $maxMessagePerPoll = self::DEFAULT_MAX_MESSAGES_PER_POLL,
        /**
         * 0, if no timeout given
         */
        public readonly int $executionTimeLimitInMilliseconds = self::DEFAULT_EXECUTION_TIME_LIMIT_IN_MILLISECONDS,
        public readonly ?RetryTemplateBuilder $connectionRetryTemplate = null,
        ?bool $withSignalInterceptors = null,
        public readonly string $triggerReferenceName = '',
        public readonly string $taskExecutorName = '',
        public readonly bool $stopOnError = self::DEFAULT_STOP_ON_ERROR,
        public readonly bool $finishWhenNoMessages = self::DEFAULT_FINISH_WHEN_NO_MESSAGES,
        public readonly ?string $fixedRateExpression = null,
        public readonly ?string $cronExpression = null,
        public readonly ?string $polledChannelName = null,
    ) {
        $this->withSignalInterceptors = $withSignalInterceptors ?? extension_loaded('pcntl');
    }

    public static function create(string $endpointId): self
    {
        return new self($endpointId);
    }

    /**
     * @param int $executionTimeLimitInMilliseconds Maximum execution of running consumer. Take under that while debugging with xdebug it should be set to 0 to avoid exiting consumer to early.
     */
    public function withTestingSetup(int $handledMessageLimit = 100, int $executionTimeLimitInMilliseconds = 100, bool $stopOnError = true): self
    {
        $pollingMetadata = $this
            ->withHandledMessageLimit($handledMessageLimit)
            ->withStopOnError($stopOnError);

        if ($executionTimeLimitInMilliseconds) {
            $pollingMetadata = $pollingMetadata
                ->withExecutionTimeLimitInMilliseconds($executionTimeLimitInMilliseconds);
        }

        return $pollingMetadata;
    }

    public static function createFrom(PollingMetadata $pollingMetadata): self
    {
        return $pollingMetadata->copyWith([]);
    }

    public function withStopOnError(bool $stopOnError): self
    {
        return $this->copyWith(['stopOnError' => $stopOnError]);
    }

    public function withCron(string $cron): PollingMetadata
    {
        return $this->copyWith(['cron' => $cron]);
    }

    public function withErrorChannelName(string $errorChannelName): PollingMetadata
    {
        return $this->copyWith(['errorChannelName' => $errorChannelName]);
    }

    public function applyExecutionPollingMetadata(?ExecutionPollingMetadata $executionPollingMetadata): self
    {
        if (! $executionPollingMetadata) {
            return $this;
        }

        $copy = $this;

        if (! is_null($executionPollingMetadata->stopOnError)) {
            $copy = $copy->withStopOnError($executionPollingMetadata->stopOnError);
        }
        if (! is_null($executionPollingMetadata->handledMessageLimit)) {
            $copy = $copy->withHandledMessageLimit($executionPollingMetadata->handledMessageLimit);
        }
        if (! is_null($executionPollingMetadata->executionTimeLimitInMilliseconds)) {
            $copy = $copy->withExecutionTimeLimitInMilliseconds($executionPollingMetadata->executionTimeLimitInMilliseconds);
        }
        if (! is_null($executionPollingMetadata->memoryLimitInMegabytes)) {
            $copy = $copy->withMemoryLimitInMegaBytes($executionPollingMetadata->memoryLimitInMegabytes);
        }
        if (! is_null($executionPollingMetadata->cron)) {
            $copy = $copy->withCron($executionPollingMetadata->cron);
        }
        if (! is_null($executionPollingMetadata->finishWhenNoMessages)) {
            $copy = $copy->withFinishWhenNoMessages($executionPollingMetadata->finishWhenNoMessages);
        }
        if (! is_null($executionPollingMetadata->executionAmountLimit)) {
            $copy = $copy->withExecutionAmountLimit($executionPollingMetadata->executionAmountLimit);
        }

        return $copy;
    }

    public function withMaxMessagePerPoll(int $maxMessagePerPoll): PollingMetadata
    {
        return $this->copyWith(['maxMessagePerPoll' => $maxMessagePerPoll]);
    }

    public function withTriggerReferenceName(string $triggerReferenceName): PollingMetadata
    {
        return $this->copyWith(['triggerReferenceName' => $triggerReferenceName]);
    }

    public function withTaskExecutorName(string $taskExecutorName): PollingMetadata
    {
        return $this->copyWith(['taskExecutorName' => $taskExecutorName]);
    }

    public function withEnabledErrorChannel(bool $isErrorChannelEnabled): PollingMetadata
    {
        return $this->copyWith(['isErrorChannelEnabled' => $isErrorChannelEnabled]);
    }

    public function withFixedRateInMilliseconds(int $fixedRateInMilliseconds): PollingMetadata
    {
        return $this->copyWith(['fixedRateInMilliseconds' => $fixedRateInMilliseconds]);
    }

    public function withInitialDelayInMilliseconds(int $initialDelayInMilliseconds): PollingMetadata
    {
        return $this->copyWith(['initialDelayInMilliseconds' => $initialDelayInMilliseconds]);
    }

    public function withConnectionRetryTemplate(RetryTemplateBuilder $retryTemplateBuilder): PollingMetadata
    {
        return $this->copyWith(['connectionRetryTemplate' => $retryTemplateBuilder]);
    }

    public function withExecutionTimeLimitInMilliseconds(int $executionTimeLimitInMilliseconds): PollingMetadata
    {
        return $this->copyWith(['executionTimeLimitInMilliseconds' => $executionTimeLimitInMilliseconds]);
    }

    public function withHandledMessageLimit(int $handledMessageLimit): PollingMetadata
    {
        return $this->copyWith(['handledMessageLimit' => $handledMessageLimit]);
    }

    public function withMemoryLimitInMegaBytes(int $memoryLimit): PollingMetadata
    {
        return $this->copyWith(['memoryLimitInMegabytes' => $memoryLimit]);
    }

    public function withSignalInterceptors(bool $withSignalInterceptors): PollingMetadata
    {
        return $this->copyWith(['withSignalInterceptors' => $withSignalInterceptors]);
    }

    /**
     * How many times Consumer should be executed before it will be stopped.
     * It takes under consideration polling when no messages are received.
     */
    public function withExecutionAmountLimit(int $executionAmountLimit): PollingMetadata
    {
        return $this->copyWith(['executionAmountLimit' => $executionAmountLimit]);
    }

    public function withFinishWhenNoMessages(bool $finishWhenNoMessages): PollingMetadata
    {
        return $this->copyWith([
            'finishWhenNoMessages' => $finishWhenNoMessages,
            'handledMessageLimit' => 0,
            'executionAmountLimit' => 0,
            'executionTimeLimitInMilliseconds' => 0,
        ]);
    }

    public function getErrorChannelName(): string
    {
        return $this->stopOnError ? '' : $this->errorChannelName;
    }

    public function getEndpointId(): string
    {
        return $this->endpointId;
    }

    public function withPolledChannelName(string $polledChannelName): self
    {
        return $this->copyWith(['polledChannelName' => $polledChannelName]);
    }

    public function hasFixedRateExpression(): bool
    {
        return $this->fixedRateExpression !== null;
    }

    public function hasCronExpression(): bool
    {
        return $this->cronExpression !== null;
    }

    public function withFixedRateExpression(?string $fixedRateExpression): PollingMetadata
    {
        return $this->copyWith(['fixedRateExpression' => $fixedRateExpression]);
    }

    public function withCronExpression(?string $cronExpression): PollingMetadata
    {
        return $this->copyWith(['cronExpression' => $cronExpression]);
    }

    private function copyWith(array $changedFields): self
    {
        return new self(...[...get_object_vars($this), ...$changedFields]);
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
