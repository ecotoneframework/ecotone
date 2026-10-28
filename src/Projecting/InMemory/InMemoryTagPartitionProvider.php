<?php

declare(strict_types=1);

namespace Ecotone\Projecting\InMemory;

use function array_slice;
use function count;

use Ecotone\EventSourcing\EventStore\InMemoryEventStore;
use Ecotone\Projecting\PartitionProvider;
use Ecotone\Projecting\StreamFilter;

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
final class InMemoryTagPartitionProvider implements PartitionProvider
{
    /**
     * @param string[] $tagPartitionedProjectionNames
     */
    public function __construct(
        private InMemoryEventStore $eventStore,
        private array $tagPartitionedProjectionNames,
    ) {
    }

    public function canHandle(string $projectionName): bool
    {
        return in_array($projectionName, $this->tagPartitionedProjectionNames, true);
    }

    public function count(StreamFilter $filter): int
    {
        return count($this->eventStore->valuesOfTag($filter->tag));
    }

    public function partitions(StreamFilter $filter, ?int $limit = null, int $offset = 0): iterable
    {
        return array_slice($this->eventStore->valuesOfTag($filter->tag), $offset, $limit);
    }
}
