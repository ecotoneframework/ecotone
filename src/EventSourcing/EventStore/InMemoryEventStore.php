<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing\EventStore;

use Ecotone\EventSourcing\EventStore;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\Event;

use function in_array;
use function is_array;
use function preg_match;

/**
 * In-memory implementation of EventStore for testing purposes
 * licence Apache-2.0
 */
final class InMemoryEventStore implements EventStore
{
    private array $streams = [];

    public function create(string $streamName, array $streamEvents = [], array $streamMetadata = []): void
    {
        if (isset($this->streams[$streamName])) {
            throw new InvalidArgumentException("Stream {$streamName} already exists");
        }

        $this->streams[$streamName] = [
            'events' => $this->convertToEvents($streamEvents),
            'metadata' => $streamMetadata,
        ];
    }

    public function appendTo(string $streamName, array $streamEvents): void
    {
        if (! isset($this->streams[$streamName])) {
            $this->create($streamName, $streamEvents);
            return;
        }

        foreach ($this->convertToEvents($streamEvents) as $event) {
            $this->streams[$streamName]['events'][] = $event;
        }
    }

    public function delete(string $streamName): void
    {
        unset($this->streams[$streamName]);
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
        if ($fromNumber < 1) {
            throw new InvalidArgumentException('fromNumber must be >= 1');
        }

        if ($count !== null && $count < 1) {
            throw new InvalidArgumentException('count must be >= 1 or null');
        }

        if (! isset($this->streams[$streamName])) {
            throw new InvalidArgumentException("Stream {$streamName} not found");
        }

        if ($metadataMatcher === null) {
            $metadataMatcher = new MetadataMatcher();
        }

        $found = 0;
        $result = [];

        foreach ($this->streams[$streamName]['events'] as $key => $event) {
            $position = $key + 1;

            if ($position >= $fromNumber
                && $this->matchesMetadata($metadataMatcher, $event->getMetadata())
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
            throw new InvalidArgumentException('count must be >= 1 or null');
        }

        if (! isset($this->streams[$streamName])) {
            throw new InvalidArgumentException("Stream {$streamName} not found");
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

            if ($this->matchesMetadata($metadataMatcher, $event->getMetadata())
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
     * Used for converting to Prooph event store
     * @return array<string, array{events: Event[], metadata: array}>
     */
    public function getAllStreams(): array
    {
        return $this->streams;
    }

    /**
     * @param Event[]|object[]|array[] $events
     * @return Event[]
     */
    private function convertToEvents(array $events): array
    {
        $result = [];
        foreach ($events as $event) {
            if ($event instanceof Event) {
                $result[] = $event;
            } elseif (is_array($event)) {
                // Arrays are not supported directly, they need to be wrapped in an object
                $result[] = Event::createWithType('array', $event);
            } else {
                $result[] = Event::create($event);
            }
        }
        return $result;
    }

    private function matchesMetadata(MetadataMatcher $metadataMatcher, array $metadata): bool
    {
        foreach ($metadataMatcher->data() as $match) {
            if ($match['fieldType'] !== FieldType::METADATA) {
                continue;
            }

            $field = $match['field'];

            if (! isset($metadata[$field])) {
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
        foreach ($metadataMatcher->data() as $match) {
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
        $metadata = $event->getMetadata();

        return match ($field) {
            'uuid', 'message_id', 'messageId' => $metadata[MessageHeaders::MESSAGE_ID] ?? null,
            'event_name', 'message_name', 'messageName' => $event->getEventName(),
            'created_at', 'createdAt', 'timestamp' => $metadata[MessageHeaders::TIMESTAMP] ?? null,
            default => throw new InvalidArgumentException("Unexpected field '{$field}' given"),
        };
    }

    private function match(Operator $operator, mixed $value, mixed $expected): bool
    {
        return match ($operator) {
            Operator::EQUALS => $value === $expected,
            Operator::GREATER_THAN => $value > $expected,
            Operator::GREATER_THAN_EQUALS => $value >= $expected,
            Operator::IN => in_array($value, $expected, true),
            Operator::LOWER_THAN => $value < $expected,
            Operator::LOWER_THAN_EQUALS => $value <= $expected,
            Operator::NOT_EQUALS => $value !== $expected,
            Operator::NOT_IN => ! in_array($value, $expected, true),
            Operator::REGEX => (bool) preg_match('/' . $expected . '/', (string) $value),
        };
    }
}
