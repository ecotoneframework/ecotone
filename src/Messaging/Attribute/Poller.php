<?php

namespace Ecotone\Messaging\Attribute;

use Attribute;
use Ecotone\Messaging\Endpoint\PollingMetadata;

#[Attribute]
/**
 * licence Apache-2.0
 */
class Poller
{
    private string $cron;
    private string $errorChannelName;
    /**
     * @var int in milliseconds
     */
    private int $fixedRateInMilliseconds;
    /**
     * @var int in milliseconds
     */
    private int $initialDelayInMilliseconds;
    /**
     * How much ram can poller use before stopping
     */
    private int $memoryLimitInMegabytes;
    /**
     * How many messages should poller handle before stopping
     */
    private int $handledMessageLimit;
    /**
     * How long should poller handle messages before stopping
     */
    private int $executionTimeLimitInMilliseconds;
    /**
     * Expression to evaluate for fixed rate in milliseconds at runtime
     */
    private ?string $fixedRateExpression;
    /**
     * Expression to evaluate for cron schedule at runtime
     */
    private ?string $cronExpression;

    public function __construct(string $cron = '', string $errorChannelName = '', int $fixedRateInMilliseconds = PollingMetadata::DEFAULT_FIXED_RATE, int $initialDelayInMilliseconds = PollingMetadata::DEFAULT_INITIAL_DELAY, int $memoryLimitInMegabytes = PollingMetadata::DEFAULT_MEMORY_LIMIT_MEGABYTES, int $handledMessageLimit = PollingMetadata::DEFAULT_HANDLED_MESSAGE_LIMIT, int $executionTimeLimitInMilliseconds  = PollingMetadata::DEFAULT_EXECUTION_TIME_LIMIT_IN_MILLISECONDS, ?string $fixedRateExpression = null, ?string $cronExpression = null)
    {
        $this->cron                             = $cron;
        $this->errorChannelName                 = $errorChannelName;
        $this->fixedRateInMilliseconds          = $fixedRateInMilliseconds;
        $this->initialDelayInMilliseconds       = $initialDelayInMilliseconds;
        $this->memoryLimitInMegabytes           = $memoryLimitInMegabytes;
        $this->handledMessageLimit              = $handledMessageLimit;
        $this->executionTimeLimitInMilliseconds = $executionTimeLimitInMilliseconds;
        $this->fixedRateExpression              = $fixedRateExpression;
        $this->cronExpression                   = $cronExpression;
    }

    public function getCron(): string
    {
        return $this->cron;
    }

    public function getErrorChannelName(): string
    {
        return $this->errorChannelName;
    }

    public function getFixedRateInMilliseconds(): int
    {
        return $this->fixedRateInMilliseconds;
    }

    public function getInitialDelayInMilliseconds(): int
    {
        return $this->initialDelayInMilliseconds;
    }

    public function getMemoryLimitInMegabytes(): int
    {
        return $this->memoryLimitInMegabytes;
    }

    public function getHandledMessageLimit(): int
    {
        return $this->handledMessageLimit;
    }

    public function getExecutionTimeLimitInMilliseconds(): int
    {
        return $this->executionTimeLimitInMilliseconds;
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
}
