<?php

declare(strict_types=1);

namespace Ecotone\Api\ExtensionObject;

/**
 * licence Apache-2.0
 *
 * @link https://docs.ecotone.tech/modelling/recovering-tracing-and-monitoring/resiliency/retries
 */
final class InstantRetryConfiguration
{
    private function __construct(public readonly bool $isEnabledForCommandBus, public readonly int $commandBusRetryTimes, public readonly array $commandBuExceptions, public readonly bool $isEnabledForAsynchronousEndpoints, public readonly int $asynchronousRetryTimes, public readonly array $asynchronousExceptions)
    {
    }

    public static function createWithDefaults(): self
    {
        return new self(false, 3, [], true, 3, []);
    }

    public function withCommandBusRetry(bool $isEnabled, int $retryTimes = 3, array $retryExceptions = []): self
    {
        return new self($isEnabled, $retryTimes, $retryExceptions, $this->isEnabledForAsynchronousEndpoints, $this->asynchronousRetryTimes, $this->asynchronousExceptions);
    }

    public function withAsynchronousEndpointsRetry(bool $isEnabled, int $retryTimes = 3, array $retryExceptions = []): self
    {
        return new self($this->isEnabledForCommandBus, $this->commandBusRetryTimes, $this->commandBuExceptions, $isEnabled, $retryTimes, $retryExceptions);
    }
}
