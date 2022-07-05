<?php


namespace Ecotone\Messaging\Attribute;

use Ecotone\Messaging\Endpoint\PollingMetadata;

#[\Attribute]
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

    public function __construct(string $cron = "", string $errorChannelName = "", int $fixedRateInMilliseconds = PollingMetadata::DEFAULT_FIXED_RATE, int $initialDelayInMilliseconds = PollingMetadata::DEFAULT_INITIAL_DELAY, int $memoryLimitInMegabytes = PollingMetadata::DEFAULT_MEMORY_LIMIT_MEGABYTES, int $handledMessageLimit = PollingMetadata::DEFAULT_HANDLED_MESSAGE_LIMIT, int $executionTimeLimitInMilliseconds  = PollingMetadata::DEFAULT_EXECUTION_TIME_LIMIT_IN_MILLISECONDS)
    {
        $this->cron                             = $cron;
        $this->errorChannelName                 = $errorChannelName;
        $this->fixedRateInMilliseconds          = $fixedRateInMilliseconds;
        $this->initialDelayInMilliseconds       = $initialDelayInMilliseconds;
        $this->memoryLimitInMegabytes           = $memoryLimitInMegabytes;
        $this->handledMessageLimit              = $handledMessageLimit;
        $this->executionTimeLimitInMilliseconds = $executionTimeLimitInMilliseconds;
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
}