<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting;

use InvalidArgumentException;
use Throwable;

class ProjectingManager
{
    public function __construct(
        private ProjectionStateStorage $projectionStateStorage,
        private ProjectorExecutor $projectorExecutor,
        private StreamSource $streamSource,
        private PartitionProvider $partitionProvider,
        private string $projectionName,
        private int $batchSize = 1000,
        private bool $automaticInitialization = true,
    ) {
        if ($batchSize < 1) {
            throw new InvalidArgumentException('Batch size must be at least 1');
        }
    }

    // This is the method that is linked to the event bus routing channel
    public function execute(?string $partitionKey = null, bool $manualInitialization = false): void
    {
        do {
            $transaction = $this->projectionStateStorage->beginTransaction();
            try {
                $projectionState = $this->loadOrInitializePartitionState($partitionKey, $manualInitialization);
                if ($projectionState === null) {
                    $transaction->commit();
                    return;
                }

                $streamPage = $this->streamSource->load($projectionState->lastPosition, $this->batchSize, $partitionKey);

                $userState = $projectionState->userState;
                foreach ($streamPage->events as $event) {
                    $userState = $this->projectorExecutor->project($event, $userState);
                }
                $projectionState = $projectionState
                    ->withLastPosition($streamPage->lastPosition)
                    ->withUserState($userState);

                if (count($streamPage->events) === 0 && $manualInitialization) {
                    // If we are forcing execution and there are no new events, we still want to enable the projection if it was uninitialized
                    $projectionState = $projectionState->withStatus(ProjectionInitializationStatus::INITIALIZED);
                }

                $this->projectionStateStorage->savePartition($projectionState);
                $transaction->commit();
            } catch (Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }
        } while (count($streamPage->events) > 0);
    }

    public function loadState(?string $partitionKey = null): ProjectionPartitionState
    {
        return $this->projectionStateStorage->loadPartition($this->projectionName, $partitionKey);
    }

    public function init(): void
    {
        $this->projectionStateStorage->init($this->projectionName);

        $this->projectorExecutor->init();
    }

    public function delete(): void
    {
        $this->projectionStateStorage->delete($this->projectionName);

        $this->projectorExecutor->delete();
    }

    public function backfill(): void
    {
        foreach ($this->partitionProvider->partitions() as $partition) {
            $this->execute($partition, true);
        }
    }

    private function loadOrInitializePartitionState(?string $partitionKey, bool $manualInitialization): ?ProjectionPartitionState
    {
        $projectionState = $this->projectionStateStorage->loadPartition($this->projectionName, $partitionKey);

        if ($projectionState) {
            return $projectionState;
        }

        if ($manualInitialization || $this->automaticInitialization) {
            $projectionState = $this->projectionStateStorage->initPartition($this->projectionName, $partitionKey);
            if ($projectionState) {
                $this->projectorExecutor->init();
            } else {
                // Someone else initialized it in the meantime, reload the state
                $projectionState = $this->projectionStateStorage->loadPartition($this->projectionName, $partitionKey);
            }
            return $projectionState;
        }
        return null;
    }
}
