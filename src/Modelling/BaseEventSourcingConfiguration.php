<?php

namespace Ecotone\Modelling;

use Ecotone\Api\Gateway\DocumentStore;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;

/**
 * licence Apache-2.0
 */
class BaseEventSourcingConfiguration implements DefinedObject
{
    public const DEFAULT_SNAPSHOT_TRIGGER_THRESHOLD = 100;

    public function __construct(
        private array $snapshotsAggregateClasses = [],
    ) {

    }

    public static function withDefaults(): self
    {
        return new self();
    }

    public function withSnapshotsFor(array|string $aggregateClassToSnapshot, int $thresholdTrigger = self::DEFAULT_SNAPSHOT_TRIGGER_THRESHOLD, string $documentStore = DocumentStore::class): static
    {
        foreach ((array) $aggregateClassToSnapshot as $aggregateClass) {
            $this->snapshotsAggregateClasses[$aggregateClass] = [
                'thresholdTrigger' => $thresholdTrigger,
                'documentStore' => $documentStore,
            ];
        }

        return $this;
    }

    public function getSnapshotsConfig(): array
    {
        return $this->snapshotsAggregateClasses;
    }

    public function getSnapshotTriggerThresholdFor(string $className): int
    {
        return $this->snapshotsAggregateClasses[$className]['thresholdTrigger'] ?? self::DEFAULT_SNAPSHOT_TRIGGER_THRESHOLD;
    }

    public function useSnapshotFor(string $className): bool
    {
        return array_key_exists($className, $this->snapshotsAggregateClasses);
    }

    public function getDocumentStoreReferenceFor(string $className): string
    {
        return $this->snapshotsAggregateClasses[$className]['documentStore'] ?? DocumentStore::class;
    }

    public function getDefinition(): Definition
    {
        return new Definition(
            self::class,
            [
                $this->snapshotsAggregateClasses,
            ]
        );
    }
}
