<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing\Tagging;

use function array_diff_key;
use function array_slice;
use function count;

use Ecotone\Api\EventSourcing\AppendCondition;
use Ecotone\Api\EventSourcing\Event;
use Ecotone\Api\EventSourcing\EventCriteria;

use function is_object;
use function min;

use const PHP_INT_MAX;

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
final class TagResolver
{
    public function __construct(
        private readonly EventTagRegistry $eventTagRegistry,
        private readonly AggregateCounterTags $aggregateCounterTags,
    ) {
    }

    /**
     * @param array<object|array> $events
     */
    public function resolveEvents(array $events): EventsTags
    {
        $perEvent = [];
        foreach ($events as $event) {
            $perEvent[] = $this->tagsCarriedBy($event);
        }

        return new EventsTags($perEvent);
    }

    /**
     * @param array<object|array> $events
     */
    public function resolveAppend(array $events, ?AppendCondition $appendCondition): AppendedTags
    {
        return $this->resolve($events, $appendCondition, bumpsEveryConditionTag: false, incrementsUncapturedTags: $appendCondition?->hasTagCondition() ?? false);
    }

    public function resolveGuardedBump(AppendCondition $appendCondition): AppendedTags
    {
        return $this->resolve([], $appendCondition, bumpsEveryConditionTag: true, incrementsUncapturedTags: false);
    }

    /**
     * @return array<string, array{name: string, value: string}>
     */
    public function tagsOfCriteria(EventCriteria $criteria): array
    {
        $tags = [];
        foreach ($criteria->branches() as $branch) {
            foreach ($branch->tags as $tag) {
                $tags[TagKey::of($tag['name'], $tag['value'])] = $tag;
            }
        }

        return $tags;
    }

    /**
     * @return array<string, array{name: string, value: string}>
     */
    public function countedTagsOfCriteria(EventCriteria $criteria): array
    {
        $tags = [];
        foreach ($this->tagsOfCriteria($criteria) as $key => $tag) {
            if (! $this->eventTagRegistry->isFilterOnly($tag['name'])) {
                $tags[$key] = $tag;
            }
        }

        return $tags;
    }

    /**
     * @return array{name: string, value: string}|null
     */
    public function positionTagOf(EventCriteria $branch): ?array
    {
        foreach ($branch->tags as $tag) {
            if (! $this->eventTagRegistry->isFilterOnly($tag['name'])) {
                return $tag;
            }
        }

        return $branch->tags[0] ?? null;
    }

    /**
     * @return array<string, int>
     */
    public function pushDownableTagSequenceLowerBounds(EventCriteria $criteria): array
    {
        $bounds = [];
        $unsafe = [];
        foreach ($criteria->branches() as $branch) {
            $positionTag = $this->positionTagOf($branch);
            $positionTagKey = $positionTag === null ? null : TagKey::of($positionTag['name'], $positionTag['value']);

            foreach ($branch->tags as $tag) {
                $tagKey = TagKey::of($tag['name'], $tag['value']);
                if ($tagKey !== $positionTagKey || $branch->tagSequenceLowerBound === 0) {
                    $unsafe[$tagKey] = true;
                }
            }

            if ($positionTagKey !== null && $branch->tagSequenceLowerBound > 0) {
                $bounds[$positionTagKey] = min($bounds[$positionTagKey] ?? PHP_INT_MAX, $branch->tagSequenceLowerBound);
            }
        }

        return array_diff_key($bounds, $unsafe);
    }

    /**
     * @param Event[] $eventsInSequenceOrder
     * @return Event[]
     */
    public function pageOf(array $eventsInSequenceOrder, EventCriteria $criteria): array
    {
        if ($criteria->limit === null || count($eventsInSequenceOrder) <= $criteria->limit) {
            return $eventsInSequenceOrder;
        }

        $positionTag = $this->positionTagOf($criteria);
        $positionTagKey = TagKey::of($positionTag['name'], $positionTag['value']);
        $page = array_slice($eventsInSequenceOrder, 0, $criteria->limit);
        $lastSequenceOfPage = MatchedTagSequences::of($page[$criteria->limit - 1], $positionTagKey);

        foreach (array_slice($eventsInSequenceOrder, $criteria->limit) as $event) {
            if (MatchedTagSequences::of($event, $positionTagKey) !== $lastSequenceOfPage) {
                break;
            }
            $page[] = $event;
        }

        return $page;
    }

    /**
     * @param Event[] $events
     * @return Event[]
     */
    public function eventsMatching(array $events, EventCriteria $criteria): array
    {
        $positionTag = $criteria->tagSequenceLowerBound > 0 ? $this->positionTagOf($criteria) : null;
        $positionTagKey = $positionTag === null ? null : TagKey::of($positionTag['name'], $positionTag['value']);

        $matching = [];
        foreach ($events as $event) {
            if (! $criteria->matchesEventType($event->eventName) || ! $this->carriesAll($event, $criteria->tags)) {
                continue;
            }

            if ($positionTagKey !== null && (MatchedTagSequences::of($event, $positionTagKey) ?? 0) <= $criteria->tagSequenceLowerBound) {
                continue;
            }

            $matching[] = $event;
        }

        return $matching;
    }

    /**
     * @param array<object|array> $events
     */
    private function resolve(array $events, ?AppendCondition $appendCondition, bool $bumpsEveryConditionTag, bool $incrementsUncapturedTags): AppendedTags
    {
        $conditionVersions = [];
        foreach ($appendCondition?->expectedTagVersions() ?? [] as $expected) {
            $conditionVersions[TagKey::of($expected['name'], $expected['value'])] = $expected;
        }

        $aggregateCounterTags = $this->counterTagOfSavedAggregate($appendCondition);

        return new AppendedTags(
            $this->resolveEvents($events),
            $conditionVersions,
            $aggregateCounterTags,
            $this->aggregateTypesOfCounterTags([...$conditionVersions, ...$aggregateCounterTags]),
            $bumpsEveryConditionTag,
            $incrementsUncapturedTags,
        );
    }

    /**
     * @param array<array{name: string, value: string}> $requiredTags
     */
    private function carriesAll(Event $event, array $requiredTags): bool
    {
        $carried = [];
        foreach ($this->tagsCarriedBy($event) as $tag) {
            $carried[TagKey::of($tag['name'], $tag['value'])] = true;
        }

        foreach ($requiredTags as $requiredTag) {
            if (! isset($carried[TagKey::of($requiredTag['name'], $requiredTag['value'])])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<array{name: string, value: string, counted: bool}>
     */
    private function tagsCarriedBy(object|array $event): array
    {
        $payload = $event instanceof Event ? $event->getPayload() : $event;
        if (! is_object($payload)) {
            return [];
        }

        $tags = [];
        foreach ($this->eventTagRegistry->tagsFor($payload) as $tag) {
            $tags[] = ['name' => $tag['name'], 'value' => $tag['value'], 'counted' => ! $this->eventTagRegistry->isFilterOnly($tag['name'])];
        }

        return $tags;
    }

    /**
     * @return array<string, array{name: string, value: string}>
     */
    private function counterTagOfSavedAggregate(?AppendCondition $appendCondition): array
    {
        if ($appendCondition === null || ! $appendCondition->hasAggregateCondition() || ! $this->aggregateCounterTags->counts($appendCondition->aggregateType())) {
            return [];
        }

        $counterTag = $this->aggregateCounterTags->counterTagOf($appendCondition->aggregateType(), $appendCondition->aggregateId());

        return [TagKey::of($counterTag['name'], $counterTag['value']) => $counterTag];
    }

    /**
     * @param array<string, array{name: string, value: string}> $tags
     * @return array<string, string>
     */
    private function aggregateTypesOfCounterTags(array $tags): array
    {
        $aggregateTypes = [];
        foreach ($tags as $key => $tag) {
            $aggregateType = $this->aggregateCounterTags->aggregateTypeCountedBy($tag['name']);
            if ($aggregateType !== null) {
                $aggregateTypes[$key] = $aggregateType;
            }
        }

        return $aggregateTypes;
    }
}
