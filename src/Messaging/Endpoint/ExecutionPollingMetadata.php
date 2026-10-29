<?php

namespace Ecotone\Messaging\Endpoint;

/**
 * licence Apache-2.0
 */
final class ExecutionPollingMetadata
{
    private ?int $handledMessageLimit = null;
    private ?int $executionTimeLimitInMilliseconds = null;
    private ?int $memoryLimitInMegabytes = null;
    private ?string $cron = null;
    private ?bool $stopOnError = null;
    private ?bool $finishWhenNoMessages = null;
    private ?int $executionAmountLimit = null;

    private function __construct()
    {
    }

    public static function createWithDefaults(): self
    {
        return new self();
    }

    /**
     * @TODO Ecotone 2.0 amountMessageToHandle increase to 100
     *
     * @param int $amountOfMessagesToHandle how many messages should this consumer handle before exiting
     * @param int $maxExecutionTimeInMilliseconds Maximum execution of running consumer. Take under that while debugging with xdebug it should be set to 0 to avoid exiting consumer to early.
     * @param bool $failAtError Should consumer stop when error occurs, if not message will be requeued and consumer will continue
     * @return $this
     */
    public static function createWithTestingSetup(int $amountOfMessagesToHandle = 1, int $maxExecutionTimeInMilliseconds = 100, bool $failAtError = true): self
    {
        return self::createWithDefaults()->withTestingSetup($amountOfMessagesToHandle, $maxExecutionTimeInMilliseconds, $failAtError);
    }

    /**
     * @param bool $failAtError Should consumer stop when error occurs, if not message will be requeued and consumer will continue
     */
    public static function createWithFinishWhenNoMessages(bool $failAtError = true): self
    {
        return self::createWithDefaults()
            ->withFinishWhenNoMessages(true)
            ->withStopOnError($failAtError)
            ->withExecutionTimeLimitInMilliseconds(0)
            ->withHandledMessageLimit(0);
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

    /**
     * @param int $amountOfMessagesToHandle how many messages should this consumer handle before exiting
     * @param int $maxExecutionTimeInMilliseconds Maximum execution of running consumer. Take under that while debugging with xdebug it should be set to 0 to avoid exiting consumer to early.
     * @return $this
     */
    public function withTestingSetup(int $amountOfMessagesToHandle = 1, int $maxExecutionTimeInMilliseconds = 100, bool $failAtError = true): self
    {
        return $this
            ->withHandledMessageLimit($amountOfMessagesToHandle)
            ->withStopOnError($failAtError)
            ->withExecutionTimeLimitInMilliseconds($maxExecutionTimeInMilliseconds);
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

    public function withFinishWhenNoMessages(bool $finishWhenNoMessages): ExecutionPollingMetadata
    {
        $self = clone $this;
        $self->finishWhenNoMessages = $finishWhenNoMessages;

        return $self;
    }

    public function withExecutionAmountLimit(int $limit): self
    {
        $self = clone $this;
        $self->executionAmountLimit = $limit;

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

    public function getFinishWhenNoMessages(): ?bool
    {
        return $this->finishWhenNoMessages;
    }

    public function getExecutionAmountLimit(): ?int
    {
        return $this->executionAmountLimit;
    }
}
