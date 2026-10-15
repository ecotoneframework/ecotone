<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing\Tagging;

use Ecotone\Api\EventSourcing\AppendCondition;
use Ecotone\Api\EventSourcing\EventCriteria;
use Ecotone\Api\EventSourcing\EventStore;
use Ecotone\EventSourcing\EventStore\GuardedTagBump;
use Ecotone\Modelling\AggregateIdString;
use Ecotone\Modelling\Repository\AggregateCounter;

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
 */
final class EventStoreAggregateCounter implements AggregateCounter
{
    public function __construct(
        private readonly AggregateCounterTags $aggregateCounterTags,
        private readonly EventStore $eventStore,
        private readonly GuardedTagBump $guardedTagBump,
    ) {
    }

    public function captureFor(string $aggregateClassName, array $identifiers): AppendCondition
    {
        $counterTag = $this->counterTagOf($aggregateClassName, $identifiers);

        return $counterTag === null ? AppendCondition::empty() : AppendCondition::fromCapturedVersions([$this->captureNow($counterTag)]);
    }

    public function bumpGuarded(string $aggregateClassName, array $identifiers, AppendCondition $capturedAtLoad): void
    {
        $counterTag = $this->counterTagOf($aggregateClassName, $identifiers);
        if ($counterTag === null) {
            return;
        }

        $this->guardedTagBump->bumpTagsGuarded(AppendCondition::fromCapturedVersions([
            $this->capturedVersionOf($counterTag, $capturedAtLoad) ?? $this->captureNow($counterTag),
        ]));
    }

    /**
     * @param array{name: string, value: string} $counterTag
     * @return array{name: string, value: string, expectedVersion: int}|null
     */
    private function capturedVersionOf(array $counterTag, AppendCondition $capturedAtLoad): ?array
    {
        foreach ($capturedAtLoad->expectedTagVersions() as $expectedTagVersion) {
            if ($expectedTagVersion['name'] === $counterTag['name'] && $expectedTagVersion['value'] === $counterTag['value']) {
                return $expectedTagVersion;
            }
        }

        return null;
    }

    /**
     * @param array{name: string, value: string} $counterTag
     * @return array{name: string, value: string, expectedVersion: int}
     */
    private function captureNow(array $counterTag): array
    {
        return $this->eventStore->loadByCriteria(EventCriteria::tag($counterTag['name'], $counterTag['value']))->appendCondition->expectedTagVersions()[0];
    }

    /**
     * @param array<string, mixed> $identifiers
     * @return array{name: string, value: string}|null
     */
    private function counterTagOf(string $aggregateClassName, array $identifiers): ?array
    {
        $aggregateType = $this->aggregateCounterTags->aggregateTypeOfClass($aggregateClassName);

        return $aggregateType === null ? null : $this->aggregateCounterTags->counterTagOf($aggregateType, AggregateIdString::from($identifiers));
    }
}
