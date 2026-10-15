<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing\EventStore\Tag;

use function array_filter;
use function array_intersect_key;
use function array_slice;
use function array_values;

use Ecotone\Api\EventSourcing\Event;
use Ecotone\Api\EventSourcing\EventCriteria;
use Ecotone\EventSourcing\EventStore\InMemoryEventStore;
use Ecotone\EventSourcing\Tagging\MatchedEvents;
use Ecotone\EventSourcing\Tagging\TagKey;
use Ecotone\EventSourcing\Tagging\TagResolver;

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
final class InMemoryTagIndex
{
    /**
     * @var array<string, array<string, array<array{stream: string, eventNo: int, sequence: int}>>>
     */
    private array $references = [];

    /**
     * @param array<int, array<array{name: string, value: string, sequence: int}>> $sequencedTagsPerEvent
     */
    public function record(string $streamName, int $firstEventNo, array $sequencedTagsPerEvent): void
    {
        foreach ($sequencedTagsPerEvent as $position => $tags) {
            foreach ($tags as $tag) {
                $this->references[$tag['name']][$tag['value']][] = ['stream' => $streamName, 'eventNo' => $firstEventNo + $position, 'sequence' => $tag['sequence']];
            }
        }
    }

    /**
     * @return Event[]
     */
    public function eventsMatching(InMemoryEventStore $eventStore, EventCriteria $criteria, TagResolver $tagResolver): array
    {
        $matched = new MatchedEvents();

        foreach ($criteria->branches() as $branch) {
            $tags = $branch->tags;
            if ($tags === []) {
                continue;
            }

            $positionTag = $tagResolver->positionTagOf($branch);
            $positionTagKey = TagKey::of($positionTag['name'], $positionTag['value']);

            foreach ($this->referencesCarryingAll($tags) as $referenceKey => $reference) {
                $sequencesByTagKey = $this->sequencesOf($referenceKey, $tags);
                $sequence = $sequencesByTagKey[$positionTagKey] ?? null;

                if ($sequence === null || ($branch->tagSequenceLowerBound > 0 && $sequence <= $branch->tagSequenceLowerBound)) {
                    continue;
                }

                $event = $eventStore->eventAt($reference['stream'], $reference['eventNo']);
                if ($event !== null && $branch->matchesEventType($event->eventName)) {
                    $matched->consider($reference['stream'], $reference['eventNo'], $sequence, $event, $sequencesByTagKey);
                }
            }
        }

        return $matched->inSequenceOrder();
    }

    /**
     * @param array<array{name: string, value: string}> $tags
     * @return array<string, int>
     */
    private function sequencesOf(string $referenceKey, array $tags): array
    {
        $sequences = [];
        foreach ($tags as $tag) {
            $reference = $this->referencesOf($tag)[$referenceKey] ?? null;
            if ($reference !== null) {
                $sequences[TagKey::of($tag['name'], $tag['value'])] = $reference['sequence'];
            }
        }

        return $sequences;
    }

    public function deleteStream(string $streamName): void
    {
        foreach ($this->references as $tagName => $tagValues) {
            foreach ($tagValues as $tagValue => $references) {
                $this->references[$tagName][$tagValue] = array_values(array_filter(
                    $references,
                    static fn (array $reference): bool => $reference['stream'] !== $streamName
                ));
            }
        }
    }

    /**
     * @param array<array{name: string, value: string}> $tags
     * @return array<string, array{stream: string, eventNo: int, sequence: int}>
     */
    private function referencesCarryingAll(array $tags): array
    {
        $carryingAll = $this->referencesOf($tags[0]);

        foreach (array_slice($tags, 1) as $tag) {
            $carryingAll = array_intersect_key($carryingAll, $this->referencesOf($tag));
        }

        return $carryingAll;
    }

    /**
     * @param array{name: string, value: string} $tag
     * @return array<string, array{stream: string, eventNo: int, sequence: int}>
     */
    private function referencesOf(array $tag): array
    {
        $keyed = [];
        foreach ($this->references[$tag['name']][$tag['value']] ?? [] as $reference) {
            $keyed[$reference['stream'] . "\0" . $reference['eventNo']] = $reference;
        }

        return $keyed;
    }
}
