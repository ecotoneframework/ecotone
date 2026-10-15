<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing\EventStore;

use function array_map;
use function count;

use Ecotone\Api\EventSourcing\AppendCondition;
use Ecotone\Api\EventSourcing\Event;
use Ecotone\Api\EventSourcing\EventCriteria;
use Ecotone\Api\EventSourcing\EventStore;
use Ecotone\Api\EventSourcing\FieldType;
use Ecotone\Api\EventSourcing\LoadedEvents;
use Ecotone\Api\EventSourcing\MetadataMatcher;
use Ecotone\Api\EventSourcing\Operator;
use Ecotone\Api\Messaging\ConcurrencyException;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\EventSourcing\EventStore\AppendStrategy\AppendableStore;
use Ecotone\EventSourcing\EventStore\AppendStrategy\AppendStrategy;
use Ecotone\EventSourcing\EventStore\Tag\InMemoryTagCollaborator;
use Ecotone\Messaging\Support\InvalidArgumentException;

use function in_array;
use function is_float;
use function is_int;
use function is_scalar;
use function is_string;
use function json_encode;
use function preg_match;

/**
 * In-memory implementation of EventStore for testing purposes
 * licence Apache-2.0
 */
final class InMemoryEventStore implements EventStore, AppendableStore, GuardedTagBump
{
    private array $streams = [];

    public function __construct(
        private readonly AppendStrategy $appendStrategy,
        private readonly InMemoryTagCollaborator $tagCollaborator,
        private readonly RecordedEventFormat $recordedEventFormat,
    ) {
    }

    public function appendTo(string $streamName, array $streamEvents, ?AppendCondition $appendCondition = null): void
    {
        if (! isset($this->streams[$streamName])) {
            $this->streams[$streamName] = [
                'events' => [],
                'metadata' => [],
                'eventIds' => [],
                'aggregateVersions' => [],
            ];
        }

        if ($streamEvents === []) {
            return;
        }

        $this->appendStrategy->append($this, $streamName, $streamEvents, $appendCondition);
    }

    public function loadByCriteria(EventCriteria $criteria): LoadedEvents
    {
        return $this->tagCollaborator->loadByCriteria($this, $criteria);
    }

    public function delete(string $streamName): void
    {
        unset($this->streams[$streamName]);

        $this->tagCollaborator->deleteTagIndexFor($streamName);
    }

    public function hasStream(string $streamName): bool
    {
        return isset($this->streams[$streamName]);
    }

    public function load(
        string $streamName,
        int $fromNumber = 1,
        ?int $count = null,
        ?MetadataMatcher $metadataMatcher = null,
        bool $deserialize = true
    ): iterable {
        $events = $this->loadEvents($streamName, $fromNumber, $count, $metadataMatcher);

        return $deserialize ? $events : array_map($this->recordedEventFormat->recordedDataOf(...), $events);
    }

    public function appendEventsUnconditionally(string $streamName, array $events): void
    {
        $this->appendPlainEvents($streamName, $events);
    }

    public function appendEventsWithAggregateCondition(string $streamName, array $events, AppendCondition $appendCondition): void
    {
        $this->assertAggregateVersionMatches($streamName, $appendCondition);
        $this->appendPlainEvents($streamName, $events);
    }

    public function appendEventsWithTagCondition(string $streamName, array $events, ?AppendCondition $appendCondition): void
    {
        if ($appendCondition !== null && $appendCondition->hasAggregateCondition()) {
            $this->assertAggregateVersionMatches($streamName, $appendCondition);
        }

        $this->tagCollaborator->appendEventsWithTagCondition($this, $streamName, $events, $appendCondition);
    }

    public function bumpTagsGuarded(AppendCondition $appendCondition): void
    {
        $this->tagCollaborator->bumpTagsGuarded($appendCondition);
    }

    public function nextEventNumber(string $streamName): int
    {
        return count($this->streams[$streamName]['events']) + 1;
    }

    public function eventAt(string $streamName, int $eventNo): ?Event
    {
        return $this->streams[$streamName]['events'][$eventNo - 1] ?? null;
    }

    /**
     * @param Event[]|object[]|array[] $events
     */
    private function appendPlainEvents(string $streamName, array $events): void
    {
        $recordedEvents = array_map($this->recordedEventFormat->recordedFrom(...), $events);
        $this->refuseEventsRecordedBefore($streamName, $recordedEvents);

        foreach ($recordedEvents as $recordedEvent) {
            $this->record($streamName, $recordedEvent);
        }
    }

    /**
     * @param Event[] $recordedEvents
     */
    private function refuseEventsRecordedBefore(string $streamName, array $recordedEvents): void
    {
        $eventIds = $this->streams[$streamName]['eventIds'];
        $aggregateVersions = $this->streams[$streamName]['aggregateVersions'];
        foreach ($recordedEvents as $recordedEvent) {
            $eventId = $this->eventIdOf($recordedEvent);
            if ($eventId !== null) {
                if (isset($eventIds[$eventId])) {
                    throw ConcurrencyException::forRecordedEventId($streamName, $eventId);
                }
                $eventIds[$eventId] = true;
            }

            $aggregateVersion = $this->aggregateVersionOf($recordedEvent);
            if ($aggregateVersion !== null) {
                $aggregateVersionKey = json_encode($aggregateVersion, JSON_THROW_ON_ERROR);
                if (isset($aggregateVersions[$aggregateVersionKey])) {
                    throw ConcurrencyException::forRecordedAggregateVersion($streamName, ...$aggregateVersion);
                }
                $aggregateVersions[$aggregateVersionKey] = true;
            }
        }
    }

    private function record(string $streamName, Event $recordedEvent): void
    {
        $this->streams[$streamName]['events'][] = $recordedEvent;

        $eventId = $this->eventIdOf($recordedEvent);
        if ($eventId !== null) {
            $this->streams[$streamName]['eventIds'][$eventId] = true;
        }

        $aggregateVersion = $this->aggregateVersionOf($recordedEvent);
        if ($aggregateVersion !== null) {
            $this->streams[$streamName]['aggregateVersions'][json_encode($aggregateVersion, JSON_THROW_ON_ERROR)] = true;
        }
    }

    private function eventIdOf(Event $recordedEvent): ?string
    {
        $eventId = $recordedEvent->metadata[MessageHeaders::MESSAGE_ID] ?? null;

        return $eventId === null ? null : (string) $eventId;
    }

    /**
     * @return array{0: string, 1: string, 2: string}|null
     */
    private function aggregateVersionOf(Event $recordedEvent): ?array
    {
        $metadata = $recordedEvent->metadata;
        $aggregateType = $metadata[MessageHeaders::EVENT_AGGREGATE_TYPE] ?? null;
        $aggregateId = $metadata[MessageHeaders::EVENT_AGGREGATE_ID] ?? null;
        $aggregateVersion = $metadata[MessageHeaders::EVENT_AGGREGATE_VERSION] ?? null;
        if ($aggregateType === null || $aggregateId === null || $aggregateVersion === null) {
            return null;
        }

        return [(string) $aggregateType, (string) $aggregateId, (string) $aggregateVersion];
    }

    private function assertAggregateVersionMatches(string $streamName, AppendCondition $appendCondition): void
    {
        $aggregateType = $appendCondition->aggregateType();
        $aggregateId = $appendCondition->aggregateId();
        $expectedVersion = $appendCondition->expectedAggregateVersion();

        $currentVersion = $this->currentAggregateVersion($streamName, $aggregateType, $aggregateId);
        if ($currentVersion !== $expectedVersion) {
            throw ConcurrencyException::forStaleAggregate((string) $aggregateType, $aggregateId, $expectedVersion, $currentVersion);
        }
    }

    private function currentAggregateVersion(string $streamName, ?string $aggregateType, ?string $aggregateId): int
    {
        if (! isset($this->streams[$streamName])) {
            return 0;
        }

        $maxVersion = 0;
        foreach ($this->streams[$streamName]['events'] as $event) {
            $metadata = $event->metadata;
            $eventAggregateId = $metadata[MessageHeaders::EVENT_AGGREGATE_ID] ?? null;
            if (! is_scalar($eventAggregateId) || (string) $eventAggregateId !== $aggregateId) {
                continue;
            }

            $eventAggregateType = $metadata[MessageHeaders::EVENT_AGGREGATE_TYPE] ?? null;
            if ($aggregateType !== null && (string) $eventAggregateType !== $aggregateType) {
                continue;
            }

            $version = $metadata[MessageHeaders::EVENT_AGGREGATE_VERSION] ?? 0;
            if ($version > $maxVersion) {
                $maxVersion = $version;
            }
        }

        return $maxVersion;
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
        $metadataMatcher = new MetadataMatcher();
        if ($aggregateType !== null) {
            $metadataMatcher = $metadataMatcher->withMetadataMatch(MessageHeaders::EVENT_AGGREGATE_TYPE, Operator::EQUALS, $aggregateType);
        }
        $metadataMatcher = $metadataMatcher->withMetadataMatch(MessageHeaders::EVENT_AGGREGATE_ID, Operator::EQUALS, $aggregateId);
        $metadataMatcher = $metadataMatcher->withMetadataMatch(MessageHeaders::EVENT_AGGREGATE_VERSION, Operator::GREATER_THAN_EQUALS, $fromVersion);
        if ($eventNames !== []) {
            $metadataMatcher = $metadataMatcher->withMetadataMatch('event_name', Operator::IN, $eventNames, FieldType::MESSAGE_PROPERTY);
        }

        return $this->load($streamName, 1, $count, $metadataMatcher, $deserialize);
    }

    /**
     * @return Event[]
     */
    public function loadReverse(
        string $streamName,
        ?int $fromNumber = null,
        ?int $count = null,
        ?MetadataMatcher $metadataMatcher = null,
        bool $deserialize = true
    ): iterable {
        if ($fromNumber !== null && $fromNumber < 1) {
            throw new InvalidArgumentException('fromNumber must be >= 1 or null');
        }

        if ($count !== null && $count < 1) {
            throw new InvalidArgumentException("count must be >= 1 or null, got {$count}: it caps how many events are loaded, so 0 would load none. Pass null to load every event that matches.");
        }

        if (! isset($this->streams[$streamName])) {
            return [];
        }

        if ($metadataMatcher === null) {
            $metadataMatcher = new MetadataMatcher();
        }

        $events = $this->streams[$streamName]['events'];
        $totalEvents = count($events);

        // If fromNumber is null, start from the last event
        $startPosition = $fromNumber !== null ? $fromNumber : $totalEvents;

        $found = 0;
        $result = [];

        // Iterate in reverse order
        for ($position = $startPosition; $position >= 1; $position--) {
            $key = $position - 1;

            if (! isset($events[$key])) {
                continue;
            }

            $event = $events[$key];

            if ($this->matchesMetadata($metadataMatcher, $event->metadata)
                && $this->matchesEventProperty($metadataMatcher, $event)
            ) {
                ++$found;
                $result[] = $event;

                if ($found === $count) {
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Get all streams with their events and metadata
     * Used for converting to a persistent event store
     * @return array<string, array{events: Event[], metadata: array}>
     */
    public function getAllStreams(): array
    {
        return $this->streams;
    }

    private function loadEvents(
        string $streamName,
        int $fromNumber = 1,
        ?int $count = null,
        ?MetadataMatcher $metadataMatcher = null,
    ): iterable {
        if ($fromNumber < 1) {
            throw new InvalidArgumentException('fromNumber must be >= 1');
        }

        if ($count !== null && $count < 1) {
            throw new InvalidArgumentException("count must be >= 1 or null, got {$count}: it caps how many events are loaded, so 0 would load none. Pass null to load every event that matches.");
        }

        if (! isset($this->streams[$streamName])) {
            return [];
        }

        if ($metadataMatcher === null) {
            $metadataMatcher = new MetadataMatcher();
        }

        $found = 0;
        $result = [];

        foreach ($this->streams[$streamName]['events'] as $key => $event) {
            $position = $key + 1;

            if ($position >= $fromNumber
                && $this->matchesMetadata($metadataMatcher, $event->metadata)
                && $this->matchesEventProperty($metadataMatcher, $event)
            ) {
                ++$found;
                $result[] = $event;

                if ($found === $count) {
                    break;
                }
            }
        }

        return $result;
    }

    private function matchesMetadata(MetadataMatcher $metadataMatcher, array $metadata): bool
    {
        foreach ($metadataMatcher->data as $match) {
            if ($match['fieldType'] !== FieldType::METADATA) {
                continue;
            }

            $field = $match['field'];

            if (! isset($metadata[$field])) {
                if ($match['operator'] === Operator::NOT_EQUALS || $match['operator'] === Operator::NOT_IN) {
                    continue;
                }

                return false;
            }

            if (! $this->match($match['operator'], $metadata[$field], $match['value'])) {
                return false;
            }
        }

        return true;
    }

    private function matchesEventProperty(MetadataMatcher $metadataMatcher, Event $event): bool
    {
        foreach ($metadataMatcher->data as $match) {
            if ($match['fieldType'] !== FieldType::MESSAGE_PROPERTY) {
                continue;
            }

            $value = $this->getEventPropertyValue($event, $match['field']);

            if (! $this->match($match['operator'], $value, $match['value'])) {
                return false;
            }
        }

        return true;
    }

    private function getEventPropertyValue(Event $event, string $field): mixed
    {
        $metadata = $event->metadata;

        return match ($field) {
            'uuid', 'message_id', 'messageId' => $metadata[MessageHeaders::MESSAGE_ID] ?? null,
            'event_name', 'message_name', 'messageName' => $event->eventName,
            'created_at', 'createdAt', 'timestamp' => $metadata[MessageHeaders::TIMESTAMP] ?? null,
            default => throw new InvalidArgumentException("Unexpected field '{$field}' given"),
        };
    }

    private function match(Operator $operator, mixed $value, mixed $expected): bool
    {
        return match ($operator) {
            Operator::EQUALS => $this->textForm($value) === $this->textForm($expected),
            Operator::GREATER_THAN => $value > $expected,
            Operator::GREATER_THAN_EQUALS => $value >= $expected,
            Operator::IN => in_array($this->textForm($value), array_map($this->textForm(...), $expected), true),
            Operator::LOWER_THAN => $value < $expected,
            Operator::LOWER_THAN_EQUALS => $value <= $expected,
            Operator::NOT_EQUALS => $this->textForm($value) !== $this->textForm($expected),
            Operator::NOT_IN => ! in_array($this->textForm($value), array_map($this->textForm(...), $expected), true),
            Operator::REGEX => (bool) preg_match('/' . $expected . '/', (string) $value),
        };
    }

    private function textForm(mixed $value): mixed
    {
        return is_int($value) || is_float($value) || is_string($value) ? (string) $value : $value;
    }
}
