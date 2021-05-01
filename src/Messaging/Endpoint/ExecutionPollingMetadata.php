<?php

namespace Ecotone\Messaging\Endpoint;

final class ExecutionPollingMetadata
{
    private ?int $handledMessageLimit = null;
    private ?int $executionTimeLimitInMilliseconds = null;
    private ?int $memoryLimitInMegabytes = null;
    private ?string $cron = null;
    private ?bool $stopOnError = null;

    private function __construct() {}

    public static function createWithDefaults() : self
    {
        return new self();
    }

    public function withCron(string $cron): ExecutionPollingMetadata
    {
        $self = clone $this;
        $self->cron = $cron;

        return $self;
    }

    public function withHandledMessageLimit(int $handledMessageLimit): ExecutionPollingMetadata
    {
        $self = clone $this;
        $self->handledMessageLimit = $handledMessageLimit;

        return $self;
    }

    public function withMemoryLimitInMegabytes(int $memoryLimitInMegabytes): ExecutionPollingMetadata
    {
        $self = clone $this;
        $self->memoryLimitInMegabytes = $memoryLimitInMegabytes;

        return $self;
    }

    public function withExecutionTimeLimitInMilliseconds(int $executionTimeLimitInMilliseconds): ExecutionPollingMetadata
    {
        $self = clone $this;
        $self->executionTimeLimitInMilliseconds = $executionTimeLimitInMilliseconds;

        return $self;
    }

    public function withStopOnError(bool $stopOnError): ExecutionPollingMetadata
    {
        $self = clone $this;
        $self->stopOnError = $stopOnError;

        return $self;
    }

    public function getCron(): ?string
    {
        return $this->cron;
    }

    public function getHandledMessageLimit(): ?int
    {
        return $this->handledMessageLimit;
    }

    public function getMemoryLimitInMegabytes(): ?int
    {
        return $this->memoryLimitInMegabytes;
    }

    public function getExecutionTimeLimitInMilliseconds(): ?int
    {
        return $this->executionTimeLimitInMilliseconds;
    }

    public function getStopOnError(): ?bool
    {
        return $this->stopOnError;
    }
}