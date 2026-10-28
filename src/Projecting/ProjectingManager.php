<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Projecting;

use Ecotone\Messaging\Endpoint\Interceptor\TerminationListener;
use Ecotone\Messaging\Gateway\MessagingEntrypointService;
use InvalidArgumentException;
use Throwable;

class ProjectingManager
{
    private const DEFAULT_BACKFILL_PARTITION_BATCH_SIZE = 100;
    private const DEFAULT_REBUILD_PARTITION_BATCH_SIZE = 100;

    private ?ProjectionStateStorage $projectionStateStorage = null;

    public function __construct(
        private ProjectionStateStorageRegistry $projectionStateStorageRegistry,
        private ProjectorExecutor              $projectorExecutor,
        private StreamSourceRegistry           $streamSourceRegistry,
        private PartitionProviderRegistry      $partitionProviderRegistry,
        private StreamFilterRegistry           $streamFilterRegistry,
        private string                         $projectionName,
        private TerminationListener            $terminationListener,
        private MessagingEntrypointService      $messagingEntrypoint,
        private int                            $eventLoadingBatchSize = 1000,
        private bool                           $automaticInitialization = true,
        private int                            $backfillPartitionBatchSize = self::DEFAULT_BACKFILL_PARTITION_BATCH_SIZE,
        private ?string                        $backfillAsyncChannelName = null,
        private int                            $rebuildPartitionBatchSize = self::DEFAULT_REBUILD_PARTITION_BATCH_SIZE,
        private ?string                        $rebuildAsyncChannelName = null,
        private bool                           $partitioned = false,
    ) {
        if ($eventLoadingBatchSize < 1) {
            throw new InvalidArgumentException('Event loading batch size must be at least 1');
        }
        if ($backfillPartitionBatchSize < 1) {
            throw new InvalidArgumentException('Backfill partition batch size must be at least 1');
        }
        if ($rebuildPartitionBatchSize < 1) {
            throw new InvalidArgumentException('Rebuild partition batch size must be at least 1');
        }
    }

    private function getProjectionStateStorage(): ProjectionStateStorage
    {
        if ($this->projectionStateStorage === null) {
            $this->projectionStateStorage = $this->projectionStateStorageRegistry->getFor($this->projectionName);
        }
        return $this->projectionStateStorage;
    }

    public function execute(?string $partitionKeyValue = null, bool $manualInitialization = false): void
    {
        do {
            $processedEvents = $this->messagingEntrypoint->sendWithHeaders(
                [],
                [
                    ProjectingHeaders::PROJECTION_PARTITION_KEY => $partitionKeyValue,
                    ProjectingHeaders::PROJECTION_CAN_INITIALIZE => $manualInitialization || $this->automaticInitialization,
                ],
                self::batchChannelFor($this->projectionName)
            );
        } while ($processedEvents > 0 && $this->terminationListener->shouldTerminate() !== true);
    }

    /**
     * @param string[] $partitionKeyValues
     */
    public function executeForPartitions(array $partitionKeyValues, bool $manualInitialization = false): void
    {
        foreach ($partitionKeyValues as $partitionKeyValue) {
            $this->execute($partitionKeyValue, $manualInitialization);
        }
    }

    public function executePartitionBatch(?string $partitionKeyValue = null, bool $canInitialize = false, bool $shouldReset = false): int
    {
        if ($canInitialize) {
            $this->ensurePartitionInitialized($partitionKeyValue);
        }

        $transaction = $this->getProjectionStateStorage()->beginTransaction();
        try {
            $projectionState = $this->loadOrInitializePartitionState($partitionKeyValue, $canInitialize);
            if ($projectionState === null) {
                $transaction->commit();
                return 0;
            }

            if ($shouldReset) {
                $this->projectorExecutor->reset($partitionKeyValue);
                $projectionState = new ProjectionPartitionState(
                    $projectionState->projectionName,
                    $projectionState->partitionKey,
                    null,
                    null,
                    $projectionState->status,
                );
            }

            $streamSource = $this->streamSourceRegistry->getFor($this->projectionName);
            $totalProcessedEvents = 0;
            $userState = $projectionState->userState;

            do {
                $streamPage = $streamSource->load($this->projectionName, $projectionState->lastPosition, $this->eventLoadingBatchSize, $partitionKeyValue);

                $batchProcessedEvents = 0;
                foreach ($streamPage->events as $event) {
                    $userState = $this->projectorExecutor->project($event, $userState, $shouldReset);
                    $batchProcessedEvents++;
                }
                if ($batchProcessedEvents > 0) {
                    $this->projectorExecutor->flush($userState, $shouldReset);
                }

                $totalProcessedEvents += $batchProcessedEvents;
                $projectionState = $projectionState
                    ->withLastPosition($streamPage->lastPosition)
                    ->withUserState($userState);
            } while ($shouldReset && $batchProcessedEvents >= $this->eventLoadingBatchSize);

            if ($totalProcessedEvents === 0 && $canInitialize) {
                $projectionState = $projectionState->withStatus(ProjectionInitializationStatus::INITIALIZED);
            }

            $this->getProjectionStateStorage()->savePartition($projectionState);
            $transaction->commit();
            return $totalProcessedEvents;
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public static function batchChannelFor(string $projectionName): string
    {
        return 'projecting_manager_batch:' . $projectionName;
    }

    public function loadState(?string $partitionKey = null): ?ProjectionPartitionState
    {
        return $this->getProjectionStateStorage()->loadPartition($this->projectionName, $partitionKey);
    }

    public function getPartitionProvider(): PartitionProvider
    {
        return $this->partitionProviderRegistry->getPartitionProviderFor($this->projectionName);
    }

    public function rebuildsAsynchronously(): bool
    {
        return $this->rebuildAsyncChannelName !== null;
    }

    public function backfillsAsynchronously(): bool
    {
        return $this->backfillAsyncChannelName !== null;
    }

    public function isPartitioned(): bool
    {
        return $this->partitioned;
    }

    public function isSetUp(): bool
    {
        return $this->getProjectionStateStorage()->loadPartition($this->projectionName, null, lock: false) !== null;
    }

    public function getProjectionName(): string
    {
        return $this->projectionName;
    }

    public function init(): void
    {
        $this->getProjectionStateStorage()->init($this->projectionName);

        $this->projectorExecutor->init();

        if (! $this->partitioned) {
            $this->getProjectionStateStorage()->initPartition($this->projectionName);
        }
    }

    public function delete(): void
    {
        $this->getProjectionStateStorage()->delete($this->projectionName);

        $this->projectorExecutor->delete();
    }

    public function prepareBackfill(): void
    {
        $this->preparePartitionBatches($this->backfillPartitionBatchSize, $this->backfillAsyncChannelName, false);
    }

    public function executeWithReset(?string $partitionKeyValue = null): void
    {
        $this->messagingEntrypoint->sendWithHeaders(
            [],
            [
                ProjectingHeaders::PROJECTION_PARTITION_KEY => $partitionKeyValue,
                ProjectingHeaders::PROJECTION_CAN_INITIALIZE => true,
                ProjectingHeaders::PROJECTION_SHOULD_RESET => true,
            ],
            self::batchChannelFor($this->projectionName)
        );
    }

    public function executeAll(bool $manualInitialization = true): void
    {
        foreach ($this->partitionsToProcess() as $partitionKeyValue) {
            $this->execute($partitionKeyValue, $manualInitialization);
        }
    }

    public function executeAllWithReset(): void
    {
        $this->delete();
        $this->init();
        $this->executeAll();
    }

    /**
     * @return iterable<string|null>
     */
    private function partitionsToProcess(): iterable
    {
        foreach ($this->streamFilterRegistry->provide($this->projectionName) as $streamFilter) {
            yield from $this->getPartitionProvider()->partitions($streamFilter);
        }
    }

    public function prepareRebuild(): void
    {
        $this->preparePartitionBatches($this->rebuildPartitionBatchSize, $this->rebuildAsyncChannelName, true);
    }

    private function preparePartitionBatches(int $partitionBatchSize, ?string $asyncChannelName, bool $shouldReset): void
    {
        $partitionsPerStreamFilter = [];
        foreach ($this->streamFilterRegistry->provide($this->projectionName) as $streamFilter) {
            $totalPartitions = $this->getPartitionProvider()->count($streamFilter);
            if ($totalPartitions > 0) {
                $partitionsPerStreamFilter[] = [$streamFilter, $totalPartitions];
            }
        }

        $totalPartitions = array_sum(array_column($partitionsPerStreamFilter, 1));
        $totalBatches = array_sum(array_map(fn (array $counted): int => (int) ceil($counted[1] / $partitionBatchSize), $partitionsPerStreamFilter));
        $batchNumber = 0;

        if ($totalBatches === 0) {
            $this->sendPartitionBatchMessage([
                ProjectingHeaders::REBUILD_BATCH_NUMBER => 0,
                ProjectingHeaders::REBUILD_BATCH_COUNT => 0,
                ProjectingHeaders::REBUILD_PARTITION_COUNT => 0,
            ], null);
        }

        foreach ($partitionsPerStreamFilter as [$streamFilter, $partitionsOfFilter]) {
            $numberOfBatches = (int) ceil($partitionsOfFilter / $partitionBatchSize);

            for ($batch = 0; $batch < $numberOfBatches; $batch++) {
                $offset = $batch * $partitionBatchSize;
                $batchNumber++;

                $headers = [
                    'partitionBatch.limit' => $partitionBatchSize,
                    'partitionBatch.offset' => $offset,
                    'partitionBatch.streamName' => $streamFilter->streamName,
                    'partitionBatch.aggregateType' => $streamFilter->aggregateType,
                    'partitionBatch.eventStoreReferenceName' => $streamFilter->eventStoreReferenceName,
                    'partitionBatch.tag' => $streamFilter->tag,
                    'partitionBatch.shouldReset' => $shouldReset,
                    ProjectingHeaders::REBUILD_BATCH_NUMBER => $batchNumber,
                    ProjectingHeaders::REBUILD_BATCH_COUNT => $totalBatches,
                    ProjectingHeaders::REBUILD_PARTITION_COUNT => $totalPartitions,
                ];

                $this->sendPartitionBatchMessage($headers, $asyncChannelName);
            }
        }
    }

    private function sendPartitionBatchMessage(array $headers, ?string $asyncChannelName): void
    {
        if ($asyncChannelName !== null) {
            $this->messagingEntrypoint->sendWithHeaders(
                $this->projectionName,
                $headers,
                $asyncChannelName,
                PartitionBatchExecutorHandler::PARTITION_BATCH_EXECUTOR_CHANNEL
            );
        } else {
            $this->messagingEntrypoint->sendWithHeaders(
                $this->projectionName,
                $headers,
                PartitionBatchExecutorHandler::PARTITION_BATCH_EXECUTOR_CHANNEL
            );
        }
    }

    private function loadOrInitializePartitionState(?string $partitionKey, bool $canInitialize): ?ProjectionPartitionState
    {
        $storage = $this->getProjectionStateStorage();
        $projectionState = $storage->loadPartition($this->projectionName, $partitionKey);

        if (! $canInitialize && $projectionState?->status === ProjectionInitializationStatus::UNINITIALIZED) {
            return null;
        }
        if ($projectionState) {
            return $projectionState;
        }

        if ($canInitialize) {
            return $storage->initPartition($this->projectionName, $partitionKey)
                ?? $storage->loadPartition($this->projectionName, $partitionKey);
        }
        return null;
    }

    /**
     * The projector's own #[ProjectionInitialization] handler may run arbitrary SQL, DDL included.
     * It must run before any database transaction is open: on MySQL, DDL issued mid-transaction
     * triggers an implicit commit, leaving the transaction object out of sync with the actual
     * connection state and the later explicit commit failing with "no active transaction".
     */
    private function ensurePartitionInitialized(?string $partitionKeyValue): void
    {
        $storage = $this->getProjectionStateStorage();
        if ($storage->loadPartition($this->projectionName, $partitionKeyValue, lock: false) !== null) {
            return;
        }

        if ($storage->initPartition($this->projectionName, $partitionKeyValue) !== null) {
            $this->projectorExecutor->init();
        }
    }
}
