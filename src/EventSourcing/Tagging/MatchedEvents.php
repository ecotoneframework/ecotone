<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing\Tagging;

use function array_map;
use function array_values;

use Ecotone\Api\EventSourcing\Event;

use function uasort;

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
final class MatchedEvents
{
    /** @var array<string, array{sequence: int, eventNo: int, event: Event, sequencesByTagKey: array<string, int>}> */
    private array $lowestSequenceMatches = [];

    /**
     * @param array<string, int> $sequencesByTagKey
     */
    public function consider(string $stream, int $eventNo, int $sequence, Event $event, array $sequencesByTagKey = []): void
    {
        $reference = $stream . "\0" . $eventNo;
        $known = $this->lowestSequenceMatches[$reference] ?? null;
        $sequencesByTagKey = [...$known['sequencesByTagKey'] ?? [], ...$sequencesByTagKey];

        if ($known !== null && $known['sequence'] <= $sequence) {
            $this->lowestSequenceMatches[$reference]['sequencesByTagKey'] = $sequencesByTagKey;

            return;
        }

        $this->lowestSequenceMatches[$reference] = ['sequence' => $sequence, 'eventNo' => $eventNo, 'event' => $event, 'sequencesByTagKey' => $sequencesByTagKey];
    }

    /**
     * @return Event[]
     */
    public function inSequenceOrder(): array
    {
        $matches = $this->lowestSequenceMatches;
        uasort($matches, static fn (array $a, array $b): int => $a['sequence'] <=> $b['sequence'] ?: $a['eventNo'] <=> $b['eventNo']);

        return array_values(array_map(
            static fn (array $match): Event => MatchedTagSequences::stampOn($match['event'], $match['sequencesByTagKey']),
            $matches
        ));
    }
}
