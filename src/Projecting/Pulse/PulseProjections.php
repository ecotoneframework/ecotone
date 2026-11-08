<?php

declare(strict_types=1);

namespace Ecotone\Projecting\Pulse;

use Ecotone\Modelling\MessageHandling\Distribution\Pulse\PulseSnapshotSection;
use Ecotone\Projecting\PartitionProviderRegistry;
use Ecotone\Projecting\ProjectionInitializationStatus;
use Ecotone\Projecting\ProjectionPartitionState;
use Ecotone\Projecting\ProjectionStateStorageRegistry;
use Ecotone\Projecting\StreamFilterRegistry;
use Ecotone\Projecting\StreamSourceRegistry;
use Throwable;

/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 *
 * @internal
 */
final class PulseProjections implements PulseSnapshotSection
{
    public const EVENTS_BEHIND_CAP = 1000;
    public const PARTITIONS_INSPECTED_CAP = 100;
    public const INITIALIZED = 'initialized';
    public const PREPARED = 'prepared';
    public const UNINITIALIZED = 'uninitialized';

    /**
     * @param array<string, array{mode: string, partitioned: bool, manualKickOff: bool, asyncChannel: string|null, rebuildAsyncChannel: string|null, backfillAsyncChannel: string|null}> $descriptions
     */
    public function __construct(
        private array $descriptions,
        private ProjectionStateStorageRegistry $stateStorages,
        private StreamSourceRegistry $streamSources,
        private PartitionProviderRegistry $partitionProviders,
        private StreamFilterRegistry $streamFilters,
    ) {
    }

    /**
     * @return list<array{name: string, mode: string, partitioned: bool, manualKickOff: bool, asyncChannel: string|null, rebuildAsyncChannel: string|null, backfillAsyncChannel: string|null, initialization: string|null, positionDigest: string|null, eventsBehind: array{events: int, atLeast: bool}|null, partitions: array{total: int, inspected: int, behind: int, atLeast: bool}|null, unavailable: string|null}>
     */
    public function snapshot(): array
    {
        $names = array_keys($this->descriptions);
        sort($names);

        return array_map(fn (string $name): array => $this->describe($name), $names);
    }

    private function describe(string $name): array
    {
        $description = $this->descriptions[$name];
        $projection = ['name' => $name, ...$description, 'initialization' => null, 'positionDigest' => null, 'eventsBehind' => null, 'partitions' => null, 'unavailable' => null];

        if ($description['mode'] === 'streaming') {
            return [...$projection, 'unavailable' => "A streaming projection keeps no position of its own: it consumes the channel {$description['asyncChannel']}, so how far it is behind is the depth of that channel, which the channels section reports."];
        }

        try {
            return $description['partitioned'] ? $this->withPartitions($projection) : $this->withEventsBehind($projection);
        } catch (Throwable $exception) {
            return [...$projection, 'unavailable' => $exception->getMessage()];
        }
    }

    private function withEventsBehind(array $projection): array
    {
        $name = $projection['name'];
        $state = $this->stateStorages->getFor($name)->loadPartition($name);
        $events = $this->streamSources->getFor($name)->load($name, $state?->lastPosition, self::EVENTS_BEHIND_CAP + 1)->events;

        return [
            ...$projection,
            'initialization' => self::initializationOf($state),
            'positionDigest' => md5($state?->lastPosition ?? ''),
            'eventsBehind' => ['events' => min(count($events), self::EVENTS_BEHIND_CAP), 'atLeast' => count($events) > self::EVENTS_BEHIND_CAP],
        ];
    }

    private function withPartitions(array $projection): array
    {
        $name = $projection['name'];
        $stateStorage = $this->stateStorages->getFor($name);
        $streamSource = $this->streamSources->getFor($name);
        $partitionProvider = $this->partitionProviders->getPartitionProviderFor($name);

        $total = 0;
        $inspected = 0;
        $behind = 0;
        $allInitialized = true;
        $positions = [];
        foreach ($this->streamFilters->provide($name) as $streamFilter) {
            $total += $partitionProvider->count($streamFilter);
            $room = self::PARTITIONS_INSPECTED_CAP - $inspected;
            if ($room <= 0) {
                continue;
            }
            foreach ($partitionProvider->partitions($streamFilter, $room) as $partitionKey) {
                $state = $stateStorage->loadPartition($name, $partitionKey);
                $inspected++;
                $positions[] = $partitionKey . '=' . ($state?->lastPosition ?? '');
                $allInitialized = $allInitialized && $state?->status === ProjectionInitializationStatus::INITIALIZED;
                if ($streamSource->load($name, $state?->lastPosition, 1, $partitionKey)->events !== []) {
                    $behind++;
                }
            }
        }

        return [
            ...$projection,
            'initialization' => $inspected > 0 && $allInitialized ? self::INITIALIZED : self::UNINITIALIZED,
            'positionDigest' => md5(implode(';', $positions)),
            'partitions' => ['total' => $total, 'inspected' => $inspected, 'behind' => $behind, 'atLeast' => $total > $inspected],
        ];
    }

    private static function initializationOf(?ProjectionPartitionState $state): string
    {
        return match (true) {
            $state === null => self::UNINITIALIZED,
            $state->status === ProjectionInitializationStatus::INITIALIZED => self::INITIALIZED,
            default => self::PREPARED,
        };
    }
}
