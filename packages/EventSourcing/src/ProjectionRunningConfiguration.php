<?php

namespace Ecotone\EventSourcing;

use Ecotone\Messaging\Support\Assert;

class ProjectionRunningConfiguration
{
    private const EVENT_DRIVEN = "event-driven";
    private const POLLING      = "polling";

    private function __construct(
        private string $projectionName,
        private string $runningType,
        private bool $initializeOnStartup = true,
        private int $amountOfCachedStreamNames = 1000,
        private int $waitBeforeCallingESWhenNoEventsFound = 100000,
        private int $persistChangesAfterAmountOfOperations = 1, // this is not yet handled
        private int $projectionLockTimeout = 1000,
        private int $updateLockTimeoutAfter = 0,
        private bool $isTestingSetup = false
    ) {}

    public static function createEventDriven(string $projectionName): static
    {
        return new self($projectionName, self::EVENT_DRIVEN);
    }

    public static function createPolling(string $projectionName) : static
    {
        return new self($projectionName, self::POLLING);
    }

    public function getProjectionName(): string
    {
        return $this->projectionName;
    }

    public function isPolling(): bool
    {
        return $this->runningType === self::POLLING;
    }

    public function isEventDriven(): bool
    {
        return $this->runningType === self::EVENT_DRIVEN;
    }

    public function isInitializedOnStartup(): bool
    {
        return $this->initializeOnStartup;
    }

    public function getAmountOfCachedStreamNames(): int
    {
        return $this->amountOfCachedStreamNames;
    }

    public function getWaitBeforeCallingESWhenNoEventsFound(): int
    {
        return $this->waitBeforeCallingESWhenNoEventsFound;
    }

    public function getPersistChangesAfterAmountOfOperations(): int
    {
        return $this->persistChangesAfterAmountOfOperations;
    }

    public function getProjectionLockTimeout(): int
    {
        return $this->projectionLockTimeout;
    }

    public function getUpdateLockTimeoutAfter(): int
    {
        return $this->updateLockTimeoutAfter;
    }

    public function withInitializeOnStartup(bool $initializeOnStartup): static
    {
        $self = clone $this;
        $self->initializeOnStartup = $initializeOnStartup;

        return $self;
    }

    public function withAmountOfCachedStreamNames(int $amountOfCachedStreamNames): static
    {
        $self = clone $this;
        $self->amountOfCachedStreamNames = $amountOfCachedStreamNames;

        return $self;
    }

    /**
     * @param int $waitBeforeCallingESWhenNoEventsFound in milliseconds
     */
    public function withWaitBeforeCallingESWhenNoEventsFound(int $waitBeforeCallingESWhenNoEventsFound): static
    {
        $self = clone $this;
        $self->waitBeforeCallingESWhenNoEventsFound = $waitBeforeCallingESWhenNoEventsFound;

        return $self;
    }

    /**
     * @param int $projectionLockTimeout in milliseconds
     */
    public function withProjectionLockTimeout(int $projectionLockTimeout): static
    {
        $self = clone $this;
        $self->projectionLockTimeout = $projectionLockTimeout;

        return $self;
    }

    /**
     * @param int $updateLockTimeoutAfter in milliseconds
     */
    public function withUpdateLockTimeoutAfter(int $updateLockTimeoutAfter): static
    {
        $self = clone $this;
        $self->updateLockTimeoutAfter = $updateLockTimeoutAfter;

        return $self;
    }

    public function isTestingSetup(): bool
    {
        return $this->isTestingSetup;
    }

    public function withTestingSetup() : static
    {
        return $this
                ->withWaitBeforeCallingESWhenNoEventsFound(0)
                ->withInitializeOnStartup(true)
                ->withProjectionLockTimeout(0)
                ->withUpdateLockTimeoutAfter(0)
                ->setTestingSetup(true);
    }

    /**
     * @param bool $isTestingSetup adds extra delay to avoid locking conflicts on running projection
     */
    private function setTestingSetup(bool $isTestingSetup): static
    {
        $self = clone $this;
        $self->isTestingSetup = $isTestingSetup;

        return $self;
    }
}