<?php


namespace Ecotone\Messaging\Endpoint;


class ExecutionPollingMetadata
{
    private ?string $cron = null;
    private ?int $handledMessageLimit = null;
    private ?int $memoryLimitInMegabytes = null;
    private ?int $executionTimeLimitInMilliseconds = null;
    private ?bool $stopOnError = null;
}