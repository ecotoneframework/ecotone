<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Projecting\InMemory;

use Ecotone\EventSourcing\EventStore\InMemoryEventStore;
use Ecotone\EventSourcing\EventStore\MetadataMatcher;
use Ecotone\EventSourcing\EventStore\Operator;
use Ecotone\Projecting\StreamPage;
use Ecotone\Projecting\StreamSource;
use ReflectionProperty;

class InMemoryEventStoreStreamSource implements StreamSource
{
    public function __construct(
        private InMemoryEventStore $eventStore,
        private ?string $streamName = null,
        private ?string $partitionHeader = null
    ) {
    }

    public function load(?string $lastPosition, int $count, ?string $partitionKey = null): StreamPage
    {
        // Position is 0-based index into the global event array (like InMemoryStreamSource)
        $from = $lastPosition !== null ? (int) $lastPosition : 0;

        // Determine which streams to read from
        $streams = $this->getStreamsToRead();

        // Collect all events from all streams
        $allEvents = [];
        foreach ($streams as $stream) {
            if (! $this->eventStore->hasStream($stream)) {
                continue;
            }

            $metadataMatcher = null;
            if ($partitionKey !== null && $this->partitionHeader !== null) {
                $metadataMatcher = (new MetadataMatcher())
                    ->withMetadataMatch($this->partitionHeader, Operator::EQUALS, $partitionKey);
            }

            // Load all events from this stream (starting from position 1)
            $events = $this->eventStore->load($stream, 1, null, $metadataMatcher);
            $allEvents = array_merge($allEvents, is_array($events) ? $events : iterator_to_array($events));
        }

        // Slice based on global position
        $events = array_slice($allEvents, $from, $count);
        $to = $from + count($events);

        return new StreamPage($events, (string) $to);
    }

    private function getStreamsToRead(): array
    {
        if ($this->streamName !== null) {
            return [$this->streamName];
        }

        // Read from all streams (global stream)
        $reflection = new ReflectionProperty($this->eventStore, 'streams');
        $reflection->setAccessible(true);
        $allStreams = array_keys($reflection->getValue($this->eventStore));

        // Filter out internal streams (starting with $)
        return array_filter($allStreams, fn ($stream) => ! str_starts_with($stream, '$'));
    }
}
