<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing\EventStore\Tag;

use function array_values;

use Ecotone\Api\EventSourcing\AppendCondition;
use Ecotone\Api\EventSourcing\EventCriteria;
use Ecotone\Api\EventSourcing\LoadedEvents;
use Ecotone\EventSourcing\EventStore\InMemoryEventStore;
use Ecotone\EventSourcing\Tagging\TagResolver;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;

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
final class EnterpriseInMemoryTagCollaborator implements InMemoryTagCollaborator
{
    private InMemoryTagVersionRegister $versions;

    private InMemoryTagIndex $index;

    public function __construct(
        private readonly TagResolver $tagResolver,
        LoggingGateway $logger,
    ) {
        $this->versions = new InMemoryTagVersionRegister($logger);
        $this->index = new InMemoryTagIndex();
    }

    public function loadByCriteria(InMemoryEventStore $eventStore, EventCriteria $criteria): LoadedEvents
    {
        $captured = $this->versions->capture($this->tagResolver->countedTagsOfCriteria($criteria));
        $events = $this->index->eventsMatching($eventStore, $criteria, $this->tagResolver);

        return new LoadedEvents($events, AppendCondition::fromCapturedVersions(array_values($captured)));
    }

    public function appendEventsWithTagCondition(InMemoryEventStore $eventStore, string $streamName, array $events, ?AppendCondition $appendCondition): void
    {
        $appended = $this->tagResolver->resolveAppend($events, $appendCondition);
        $expected = $appended->expectedVersions($this->versions->capture($appended->needingCapture()));

        $this->versions->assertUnchanged($expected);

        $firstEventNo = $eventStore->nextEventNumber($streamName);
        $eventStore->appendEventsUnconditionally($streamName, $events);
        $this->versions->bump($expected);
        $this->index->record($streamName, $firstEventNo, $appended->sequencedAfterBump($expected));
    }

    public function deleteTagIndexFor(string $streamName): void
    {
        $this->index->deleteStream($streamName);
    }

    public function bumpTagsGuarded(AppendCondition $appendCondition): void
    {
        $appended = $this->tagResolver->resolveAppend([], $appendCondition);
        $expected = $appended->expectedVersions($this->versions->capture($appended->needingCapture()));

        $this->versions->assertUnchanged($expected);
        $this->versions->bump($expected);
    }
}
