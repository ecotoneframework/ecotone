<?php

declare(strict_types=1);

namespace Ecotone\Modelling\Config\InstantRetry;

/**
 * licence Apache-2.0
 */
final class InstantRetryConfiguration
{
    private function __construct(private bool $isEnabledForCommandBus, private int $commandBusRetryTimes, private array $commandBuExceptions, private bool $isEnabledForAsynchronousEndpoints, private int $asynchronousRetryTimes, private array $asynchronousExceptions)
    {
    }

    public static function createWithDefaults(): self
    {
        /** @TODO Ecotone 2.0 asynchronous retries enabled by default */
        return new self(false, 3, [], false, 3, []);
    }

    /**
     * Enable/Disable global retry for all Command/Event Handlers
     */
    public function withCommandBusRetry(bool $isEnabled, int $retryTimes = 3, array $retryExceptions = []): self
    {
        return new self($isEnabled, $retryTimes, $retryExceptions, $this->isEnabledForAsynchronousEndpoints, $this->asynchronousRetryTimes, $this->asynchronousExceptions);
    }

    /**
     * Enable/Disable retries using attributes
     */
    public function withAsynchronousEndpointsRetry(bool $isEnabled, int $retryTimes = 3, array $retryExceptions = []): self
    {
        return new self($this->isEnabledForCommandBus, $this->commandBusRetryTimes, $this->commandBuExceptions, $isEnabled, $retryTimes, $retryExceptions);
    }

    public function isEnabledForCommandBus(): bool
    {
        return $this->isEnabledForCommandBus;
    }

    public function getCommandBusRetryTimes(): int
    {
        return $this->commandBusRetryTimes;
    }

    public function getCommandBuExceptions(): array
    {
        return $this->commandBuExceptions;
    }

    public function isEnabledForAsynchronousEndpoints(): bool
    {
        return $this->isEnabledForAsynchronousEndpoints;
    }

    public function getAsynchronousRetryTimes(): int
    {
        return $this->asynchronousRetryTimes;
    }

    public function getAsynchronousExceptions(): array
    {
        return $this->asynchronousExceptions;
    }
}
