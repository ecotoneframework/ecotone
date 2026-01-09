<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting;

use Ecotone\Messaging\Endpoint\Interceptor\TerminationListener;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use InvalidArgumentException;
use Throwable;

class ProjectingManager
{
    private const DEFAULT_BACKFILL_PARTITION_BATCH_SIZE = 100;

    public function __construct(
        private ProjectionStateStorage $projectionStateStorage,
        private ProjectorExecutor      $projectorExecutor,
        private StreamSource           $streamSource,
        private PartitionProvider      $partitionProvider,
        private StreamFilterRegistry   $streamFilterRegistry,
        private string                 $projectionName,
        private TerminationListener    $terminationListener,
        private MessagingEntrypoint    $messagingEntrypoint,
        private int                    $eventLoadingBatchSize = 1000,
        private bool                   $automaticInitialization = true,
        private int                    $backfillPartitionBatchSize = self::DEFAULT_BACKFILL_PARTITION_BATCH_SIZE,
        private ?string                $backfillAsyncChannelName = null,
    ) {
        if ($eventLoadingBatchSize < 1) {
            throw new InvalidArgumentException('Event loading batch size must be at least 1');
        }
        if ($backfillPartitionBatchSize < 1) {
            throw new InvalidArgumentException('Backfill partition batch size must be at least 1');
        }
    }

    public function execute(?string $partitionKeyValue = null, bool $manualInitialization = false): void
    {
        do {
            $processedEvents = $this->executeSingleBatch($partitionKeyValue, $manualInitialization || $this->automaticInitialization);
        } while ($processedEvents > 0 && $this->terminationListener->shouldTerminate() !== true);
    }

    /**
     * @return int Number of processed events
     */
    private function executeSingleBatch(?string $partitionKeyValue, bool $canInitialize): int
    {
        $transaction = $this->projectionStateStorage->beginTransaction();
        try {
            $projectionState = $this->loadOrInitializePartitionState($partitionKeyValue, $canInitialize);
            if ($projectionState === null) {
                $transaction->commit();
                return 0;
            }

            $streamPage = $this->streamSource->load($projectionState->lastPosition, $this->eventLoadingBatchSize, $partitionKeyValue);

            $userState = $projectionState->userState;
            $processedEvents = 0;
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
            return $processedEvents;
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public function loadState(?string $partitionKey = null): ProjectionPartitionState
    {
        return $this->projectionStateStorage->loadPartition($this->projectionName, $partitionKey);
    }

    public function getPartitionProvider(): PartitionProvider
    {
        return $this->partitionProvider;
    }

    public function getProjectionName(): string
    {
        return $this->projectionName;
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

    /**
     * Prepares backfill by calculating batches and sending messages to BackfillExecutorHandler.
     * Each batch message contains a limit and offset for processing a subset of partitions.
     * This enables the backfill to be executed synchronously or asynchronously depending on configuration.
     */
    public function prepareBackfill(): void
    {
        $streamFilters = $this->streamFilterRegistry->provide($this->projectionName);

        foreach ($streamFilters as $streamFilter) {
            $this->prepareBackfillForFilter($streamFilter);
        }
    }

    private function prepareBackfillForFilter(StreamFilter $streamFilter): void
    {
        $totalPartitions = $this->partitionProvider->count($streamFilter);

        if ($totalPartitions === 0) {
            return;
        }

        $numberOfBatches = (int) ceil($totalPartitions / $this->backfillPartitionBatchSize);

        for ($batch = 0; $batch < $numberOfBatches; $batch++) {
            $offset = $batch * $this->backfillPartitionBatchSize;

            $headers = [
                'backfill.limit' => $this->backfillPartitionBatchSize,
                'backfill.offset' => $offset,
                'backfill.streamName' => $streamFilter->streamName,
                'backfill.aggregateType' => $streamFilter->aggregateType,
                'backfill.eventStoreReferenceName' => $streamFilter->eventStoreReferenceName,
            ];

            $this->sendBackfillMessage($headers);
        }
    }

    private function sendBackfillMessage(array $headers): void
    {
        if ($this->backfillAsyncChannelName !== null) {
            $this->messagingEntrypoint->sendWithHeaders(
                $this->projectionName,
                $headers,
                $this->backfillAsyncChannelName,
                BackfillExecutorHandler::BACKFILL_EXECUTOR_CHANNEL
            );
        } else {
            $this->messagingEntrypoint->sendWithHeaders(
                $this->projectionName,
                $headers,
                BackfillExecutorHandler::BACKFILL_EXECUTOR_CHANNEL
            );
        }
    }

    /**
     * @deprecated Use prepareBackfill() instead. This method is kept for backward compatibility.
     */
    public function backfill(): void
    {
        $this->prepareBackfill();
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
