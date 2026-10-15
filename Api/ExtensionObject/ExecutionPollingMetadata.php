<?php

namespace Ecotone\Api\ExtensionObject;

/**
 * licence Apache-2.0
 *
 * @link https://docs.ecotone.tech/modelling/testing-support/testing-asynchronous-messaging#polling-metadata
 */
final class ExecutionPollingMetadata
{
    private function __construct(
        public readonly ?int $handledMessageLimit = null,
        public readonly ?int $executionTimeLimitInMilliseconds = null,
        public readonly ?int $memoryLimitInMegabytes = null,
        public readonly ?string $cron = null,
        public readonly ?bool $stopOnError = null,
        public readonly ?bool $finishWhenNoMessages = null,
        public readonly ?int $executionAmountLimit = null,
    ) {
    }

    public static function createWithDefaults(): self
    {
        return new self();
    }

    /**
     * @param int $executionTimeLimitInMilliseconds Maximum execution of running consumer. Take under that while debugging with xdebug it should be set to 0 to avoid exiting consumer to early.
     */
    public static function createWithTestingSetup(int $handledMessageLimit = 100, int $executionTimeLimitInMilliseconds = 100, bool $stopOnError = true): self
    {
        return self::createWithDefaults()->withTestingSetup($handledMessageLimit, $executionTimeLimitInMilliseconds, $stopOnError);
    }

    public static function createWithFinishWhenNoMessages(bool $stopOnError = true): self
    {
        return self::createWithDefaults()
            ->withFinishWhenNoMessages(true)
            ->withStopOnError($stopOnError)
            ->withExecutionTimeLimitInMilliseconds(0)
            ->withHandledMessageLimit(0);
    }

    public function withCron(string $cron): ExecutionPollingMetadata
    {
        return new self(...[...get_object_vars($this), 'cron' => $cron]);
    }

    public function withHandledMessageLimit(int $handledMessageLimit): ExecutionPollingMetadata
    {
        return new self(...[...get_object_vars($this), 'handledMessageLimit' => $handledMessageLimit]);
    }

    public function withMemoryLimitInMegabytes(int $memoryLimitInMegabytes): ExecutionPollingMetadata
    {
        return new self(...[...get_object_vars($this), 'memoryLimitInMegabytes' => $memoryLimitInMegabytes]);
    }

    /**
     * @param int $executionTimeLimitInMilliseconds Maximum execution of running consumer. Take under that while debugging with xdebug it should be set to 0 to avoid exiting consumer to early.
     */
    public function withTestingSetup(int $handledMessageLimit = 100, int $executionTimeLimitInMilliseconds = 100, bool $stopOnError = true): self
    {
        return $this
            ->withHandledMessageLimit($handledMessageLimit)
            ->withStopOnError($stopOnError)
            ->withExecutionTimeLimitInMilliseconds($executionTimeLimitInMilliseconds);
    }

    public function withExecutionTimeLimitInMilliseconds(int $executionTimeLimitInMilliseconds): ExecutionPollingMetadata
    {
        return new self(...[...get_object_vars($this), 'executionTimeLimitInMilliseconds' => $executionTimeLimitInMilliseconds]);
    }

    public function withStopOnError(bool $stopOnError): ExecutionPollingMetadata
    {
        return new self(...[...get_object_vars($this), 'stopOnError' => $stopOnError]);
    }

    public function withFinishWhenNoMessages(bool $finishWhenNoMessages): ExecutionPollingMetadata
    {
        return new self(...[...get_object_vars($this), 'finishWhenNoMessages' => $finishWhenNoMessages]);
    }

    public function withExecutionAmountLimit(int $limit): self
    {
        return new self(...[...get_object_vars($this), 'executionAmountLimit' => $limit]);
    }
}
