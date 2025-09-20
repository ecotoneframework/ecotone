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
        private ProjectorExecutor      $projectorExecutor,
        private StreamSource           $streamSource,
        private PartitionProvider      $partitionProvider,
        private string                 $projectionName,
        private int                    $batchSize = 1000,
    ) {
        if ($batchSize < 1) {
            throw new InvalidArgumentException('Batch size must be at least 1');
        }
    }

    // This is the method that is linked to the event bus routing channel
    public function execute(?string $partitionKey = null): void
    {
        $this->init();

        do {
            $transaction = $this->projectionStateStorage->beginTransaction();
            try {
                $projectionState = $this->projectionStateStorage->loadPartition($this->projectionName, $partitionKey);

                $streamPage = $this->streamSource->load($projectionState->lastPosition, $this->batchSize, $partitionKey);

                $userState = $projectionState->userState;
                foreach ($streamPage->events as $event) {
                    $userState = $this->projectorExecutor->project($event, $userState);
                }

                $this->projectionStateStorage->savePartition(
                    $projectionState
                        ->withLastPosition($streamPage->lastPosition)
                        ->withUserState($userState)
                );
                $transaction->commit();
            } catch (Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }
        } while (count($streamPage->events) > 0); // TODO: we should handle the transaction lifecycle here or ignore batch size
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
            $this->execute($partition);
        }
    }
}
