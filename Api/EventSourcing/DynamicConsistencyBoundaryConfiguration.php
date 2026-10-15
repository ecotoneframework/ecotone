<?php

declare(strict_types=1);

namespace Ecotone\Api\EventSourcing;

use Ecotone\Api\Gateway\DocumentStore;

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
final class DynamicConsistencyBoundaryConfiguration
{
    public const DEFAULT_SNAPSHOT_TRIGGER_THRESHOLD = 100;

    /**
     * @param string[] $filterOnlyTagNames
     * @param array<class-string, array{thresholdTrigger: int, documentStore: string}> $snapshottedModelClasses
     * @param class-string[] $classesWithoutCounterTag
     */
    private function __construct(
        public readonly array $filterOnlyTagNames,
        public readonly array $snapshottedModelClasses = [],
        public readonly array $classesWithoutCounterTag = [],
    ) {
    }

    public static function createWithDefaults(): self
    {
        return new self([]);
    }

    /**
     * @param string[] $tagNames
     */
    public function withFilterOnlyTags(array $tagNames): self
    {
        return new self($tagNames, $this->snapshottedModelClasses, $this->classesWithoutCounterTag);
    }

    /**
     * @param class-string|class-string[] $stateStoredAggregateClasses
     */
    public function withoutCounterTagFor(array|string $stateStoredAggregateClasses): self
    {
        return new self(
            $this->filterOnlyTagNames,
            $this->snapshottedModelClasses,
            [...$this->classesWithoutCounterTag, ...(array) $stateStoredAggregateClasses],
        );
    }

    /**
     * @param class-string|class-string[] $modelClasses
     */
    public function withSnapshotsFor(
        array|string $modelClasses,
        int $thresholdTrigger = self::DEFAULT_SNAPSHOT_TRIGGER_THRESHOLD,
        string $documentStore = DocumentStore::class,
    ): self {
        $snapshottedModelClasses = $this->snapshottedModelClasses;
        foreach ((array) $modelClasses as $modelClass) {
            $snapshottedModelClasses[$modelClass] = [
                'thresholdTrigger' => $thresholdTrigger,
                'documentStore' => $documentStore,
            ];
        }

        return new self($this->filterOnlyTagNames, $snapshottedModelClasses, $this->classesWithoutCounterTag);
    }
}
