<?php

declare(strict_types=1);

namespace Ecotone\Projecting\TaggedStream;

use function array_map;

use Ecotone\Api\EventSourcing\Event;
use Ecotone\Api\EventSourcing\EventCriteria;
use Ecotone\Api\EventSourcing\EventStore;
use Ecotone\Api\Projecting\PartitionTagValue;
use Ecotone\EventSourcing\Tagging\MatchedTagSequences;
use Ecotone\EventSourcing\Tagging\TagKey;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Projecting\StreamFilterRegistry;
use Ecotone\Projecting\StreamPage;
use Ecotone\Projecting\StreamSource;

use function end;
use function in_array;

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
final class TagStreamSource implements StreamSource
{
    /**
     * @param string[] $tagPartitionedProjectionNames
     */
    public function __construct(
        private EventStore $eventStore,
        private StreamFilterRegistry $streamFilterRegistry,
        private array $tagPartitionedProjectionNames,
    ) {
    }

    public function canHandle(string $projectionName): bool
    {
        return in_array($projectionName, $this->tagPartitionedProjectionNames, true);
    }

    public function load(string $projectionName, ?string $lastPosition, int $count, ?string $partitionKey = null): StreamPage
    {
        Assert::notNull($partitionKey, "Projection {$projectionName} is partitioned by a tag's value, so it loads one value of the tag at a time and cannot load without a partition key");
        $streamFilter = $this->streamFilterRegistry->provide($projectionName)[0];

        $criteria = EventCriteria::tag($streamFilter->tag, $partitionKey)->afterTagSequence((int) $lastPosition)->limit($count);
        if ($streamFilter->eventNames !== []) {
            $criteria = $criteria->ofTypes(...$streamFilter->eventNames);
        }

        $events = array_map(
            static fn (Event $event): Event => $event->withAddedMetadata([PartitionTagValue::HEADER_NAME => $partitionKey]),
            $this->eventStore->loadByCriteria($criteria)->events,
        );
        $lastEvent = end($events);

        return new StreamPage(
            $events,
            $lastEvent === false ? ($lastPosition ?? '') : (string) MatchedTagSequences::of($lastEvent, TagKey::of($streamFilter->tag, $partitionKey)),
        );
    }
}
