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
        $canInitialize = $manualInitialization || $this->automaticInitialization;
        do {
            $transaction = $this->projectionStateStorage->beginTransaction();
            $processedEvents = 0;
            try {
                $projectionState = $this->loadOrInitializePartitionState($partitionKey, $canInitialize);
                if ($projectionState === null) {
                    $transaction->commit();
                    return;
                }

                $streamPage = $this->streamSource->load($projectionState->lastPosition, $this->batchSize, $partitionKey);

                $userState = $projectionState->userState;
                foreach ($streamPage->events as $event) {
                    $userState = $this->projectorExecutor->project($event, $userState);
                    $processedEvents++;
                }
                if ($processedEvents > 0) {
                    $this->projectorExecutor->flush();
                }

                $projectionState = $projectionState
                    ->withLastPosition($streamPage->lastPosition)
                    ->withUserState($userState);

                if ($processedEvents === 0 && $canInitialize) {
                    // If we are forcing execution and there are no new events, we still want to enable the projection if it was uninitialized
                    $projectionState = $projectionState->withStatus(ProjectionInitializationStatus::INITIALIZED);
                }

                $this->projectionStateStorage->savePartition($projectionState);
                $transaction->commit();
            } catch (Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }
        } while ($processedEvents > 0);
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

    private function loadOrInitializePartitionState(?string $partitionKey, bool $canInitialize): ?ProjectionPartitionState
    {
        $projectionState = $this->projectionStateStorage->loadPartition($this->projectionName, $partitionKey);

        if (! $canInitialize && $projectionState?->status === ProjectionInitializationStatus::UNINITIALIZED) {
            // Projection is being initialized by another process
            return null;
        }
        if ($projectionState) {
            return $projectionState;
        }

        if ($canInitialize) {
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
