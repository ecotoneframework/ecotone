<?php

declare(strict_types=1);

namespace Ecotone\Tracing;

use function count;

use Ecotone\Api\EventSourcing\AppendCondition;
use Ecotone\Api\EventSourcing\DecisionModelConcurrencyException;
use Ecotone\Api\EventSourcing\EventCriteria;
use Ecotone\Api\EventSourcing\EventStore;
use Ecotone\Api\EventSourcing\LoadedEvents;
use Ecotone\Api\EventSourcing\MetadataMatcher;
use Ecotone\Api\Tracing\SpanAttribute;
use Ecotone\Api\Tracing\SpanKind;
use Throwable;

/**
 * licence Apache-2.0
 */
final class TracedEventStore implements EventStore
{
    public function __construct(
        private readonly EventStore $eventStore,
        private readonly Tracer $tracer,
    ) {
    }

    public function appendTo(string $streamName, array $streamEvents, ?AppendCondition $appendCondition = null): void
    {
        if ($appendCondition === null || ! $appendCondition->hasTagCondition()) {
            $this->eventStore->appendTo($streamName, $streamEvents, $appendCondition);

            return;
        }

        $span = $this->tracer->startSpan('Conditional Append: ' . $streamName, SpanKind::Internal, [
            SpanAttribute::MESSAGING_SYSTEM => SpanAttribute::ECOTONE,
            DynamicConsistencyBoundarySpanAttributes::TAGS => DynamicConsistencyBoundarySpanAttributes::userTags($appendCondition->expectedTagVersions()),
            DynamicConsistencyBoundarySpanAttributes::AGGREGATES => DynamicConsistencyBoundarySpanAttributes::aggregates($appendCondition->expectedTagVersions()),
            DynamicConsistencyBoundarySpanAttributes::EVENTS_APPENDED => count($streamEvents),
        ]);

        try {
            $this->eventStore->appendTo($streamName, $streamEvents, $appendCondition);
        } catch (DecisionModelConcurrencyException $conflict) {
            $span->addEvent(DynamicConsistencyBoundarySpanAttributes::CONFLICT_EVENT_NAME, $conflict->conflictFields());
            $span->fail($conflict);

            throw $conflict;
        } catch (Throwable $failure) {
            $span->fail($failure);

            throw $failure;
        }

        $span->end();
    }

    public function delete(string $streamName): void
    {
        $this->eventStore->delete($streamName);
    }

    public function hasStream(string $streamName): bool
    {
        return $this->eventStore->hasStream($streamName);
    }

    public function load(
        string $streamName,
        int $fromNumber = 1,
        ?int $count = null,
        ?MetadataMatcher $metadataMatcher = null,
        bool $deserialize = true
    ): iterable {
        return $this->eventStore->load($streamName, $fromNumber, $count, $metadataMatcher, $deserialize);
    }

    public function loadAggregateEvents(
        string $streamName,
        ?string $aggregateType,
        string $aggregateId,
        int $fromVersion = 1,
        ?int $count = null,
        array $eventNames = [],
        bool $deserialize = true
    ): iterable {
        return $this->eventStore->loadAggregateEvents($streamName, $aggregateType, $aggregateId, $fromVersion, $count, $eventNames, $deserialize);
    }

    public function loadByCriteria(EventCriteria $criteria): LoadedEvents
    {
        return $this->eventStore->loadByCriteria($criteria);
    }
}
