<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\InMemory;

use Ecotone\Projecting\NoOpTransaction;
use Ecotone\Projecting\ProjectionInitializationStatus;
use Ecotone\Projecting\ProjectionPartitionState;
use Ecotone\Projecting\ProjectionStateStorage;
use Ecotone\Projecting\Transaction;

class InMemoryProjectionStateStorage implements ProjectionStateStorage
{
    /**
     * @var array<string, ProjectionPartitionState> key is projection name
     */
    private array $projectionStates = [];

    public function loadPartition(string $projectionName, ?string $partitionKey = null, bool $lock = true): ?ProjectionPartitionState
    {
        $key = $this->getKey($projectionName, $partitionKey);
        return $this->projectionStates[$key] ?? null;
    }

    public function initPartition(string $projectionName, ?string $partitionKey = null): ?ProjectionPartitionState
    {
        $key = $this->getKey($projectionName, $partitionKey);

        if (! isset($this->projectionStates[$key])) {
            $this->projectionStates[$key] = new ProjectionPartitionState($projectionName, $partitionKey, null, null, ProjectionInitializationStatus::UNINITIALIZED);
            return $this->projectionStates[$key];
        }

        return null; // Already exists
    }

    public function savePartition(ProjectionPartitionState $projectionState): void
    {
        $key = $this->getKey($projectionState->projectionName, $projectionState->partitionKey);
        $this->projectionStates[$key] = $projectionState;
    }

    private function getKey(string $projectionName, ?string $partitionKey): string
    {
        if ($partitionKey === null) {
            return $projectionName;
        }
        return $projectionName . '-' . $partitionKey;
    }

    public function delete(string $projectionName): void
    {
        $projectionStartKey = $this->getKey($projectionName, null);
        foreach ($this->projectionStates as $key => $value) {
            if (str_starts_with($key, $projectionStartKey)) {
                unset($this->projectionStates[$key]);
            }
        }
    }

    public function init(string $projectionName): void
    {
    }

    public function beginTransaction(): Transaction
    {
        return new NoOpTransaction();
    }
}
