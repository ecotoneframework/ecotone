<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Store\Document\DocumentStore;

/**
 * licence Apache-2.0
 */
class BaseEventSourcingConfiguration implements DefinedObject
{
    public const DEFAULT_SNAPSHOT_TRIGGER_THRESHOLD = 100;

    public function __construct(private array $snapshotsAggregateClasses = [])
    {

    }

    public static function withDefaults(): self
    {
        return new self();
    }

    /**
     * @TODO Ecotone 2.0 drop it
     * @deprecated use BaseEventSourcingConfiguration::withSnapshotsFor instead
     */
    public function withSnapshots(array $aggregateClassesToSnapshot = [], int $thresholdTrigger = self::DEFAULT_SNAPSHOT_TRIGGER_THRESHOLD, string $documentStore = DocumentStore::class): static
    {
        foreach ($aggregateClassesToSnapshot as $aggregateClassToSnapshot) {
            $this->withSnapshotsFor($aggregateClassToSnapshot, $thresholdTrigger, $documentStore);
        }

        return $this;
    }

    public function withSnapshotsFor(string $aggregateClassToSnapshot, int $thresholdTrigger = self::DEFAULT_SNAPSHOT_TRIGGER_THRESHOLD, string $documentStore = DocumentStore::class): static
    {
        $this->snapshotsAggregateClasses[$aggregateClassToSnapshot] = [
            'thresholdTrigger' => $thresholdTrigger,
            'documentStore' => $documentStore,
        ];

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
