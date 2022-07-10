<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Store\Document\DocumentStore;

class BaseEventSourcingConfiguration
{
    const DEFAULT_SNAPSHOT_TRIGGER_THRESHOLD = 100;

    private array $snapshotsAggregateClasses = [];
    private int $snapshotTriggerThreshold = self::DEFAULT_SNAPSHOT_TRIGGER_THRESHOLD;
    private string $documentStoreReference = DocumentStore::class;

    public function withSnapshots(array $aggregateClassesToSnapshot = [], int $thresholdTrigger = self::DEFAULT_SNAPSHOT_TRIGGER_THRESHOLD, string $documentStore = DocumentStore::class): static
    {
        $this->snapshotsAggregateClasses = $aggregateClassesToSnapshot;
        $this->snapshotTriggerThreshold = $thresholdTrigger;
        $this->documentStoreReference = $documentStore;

        return $this;
    }

    public function getSnapshotsAggregateClasses(): array
    {
        return $this->snapshotsAggregateClasses;
    }

    public function getSnapshotTriggerThreshold(): int
    {
        return $this->snapshotTriggerThreshold;
    }

    public function getDocumentStoreReference(): string
    {
        return $this->documentStoreReference;
    }
}