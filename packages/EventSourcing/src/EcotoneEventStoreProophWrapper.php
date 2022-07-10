<?php


namespace Ecotone\EventSourcing;

use ArrayIterator;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Driver\PDOConnection;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\InMemoryConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Modelling\Event;
use Iterator;
use Prooph\EventStore\EventStore as ProophEventStore;
use Prooph\EventStore\Metadata\MetadataMatcher;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use Ramsey\Uuid\Uuid;

class EcotoneEventStoreProophWrapper implements EventStore
{
    private LazyProophEventStore $eventStore;
    private ConversionService $conversionService;
    private EventMapper $eventMapper;

    private function __construct(LazyProophEventStore $eventStore, ConversionService $conversionService, EventMapper $eventMapper)
    {
        $this->eventStore = $eventStore;
        $this->conversionService = $conversionService;
        $this->eventMapper = $eventMapper;
    }

    public static function prepare(LazyProophEventStore $eventStore, ConversionService $conversionService, EventMapper $eventMapper): static
    {
        return new self($eventStore, $conversionService, $eventMapper);
    }

    public static function prepareWithNoConversions(LazyProophEventStore $eventStore): static
    {
        return new self($eventStore, InMemoryConversionService::createWithoutConversion(), EventMapper::createEmpty());
    }

    /**
     * @inheritDoc
     */
    public function updateStreamMetadata(string $streamName, array $newMetadata): void
    {
        $this->eventStore->updateStreamMetadata(new StreamName($streamName), $newMetadata);
    }

    /**
     * @inheritDoc
     */
    public function create(string $streamName, array $streamEvents, array $streamMetadata): void
    {
        $this->eventStore->create(new Stream(new StreamName($streamName), $this->convertProophEvents($streamEvents), $streamMetadata));
    }

    /**
     * @param Event[]|object[]|array[] $events
     */
    private function convertProophEvents(array $events): ArrayIterator
    {
        $proophEvents = [];
        foreach ($events as $eventToConvert) {
            if ($eventToConvert instanceof ProophMessage) {
                $proophEvents[] = $eventToConvert;

                continue;
            }

            if ($eventToConvert instanceof Event) {
                $payload = $eventToConvert->getPayload();
                $metadata = $eventToConvert->getMetadata();
            }else {
                $payload = $eventToConvert;
                $metadata = [];
                $eventToConvert = Event::create($payload);
            }

            $proophEvents[] = new ProophMessage(
                array_key_exists(MessageHeaders::MESSAGE_ID, $metadata) ? Uuid::fromString($metadata[MessageHeaders::MESSAGE_ID]) : Uuid::uuid4(),
                array_key_exists(MessageHeaders::TIMESTAMP, $metadata) ? new DateTimeImmutable("@" . $metadata[MessageHeaders::TIMESTAMP], new DateTimeZone('UTC')) : new DateTimeImmutable("now", new DateTimeZone('UTC')),
                is_array($payload) ? $payload : $this->conversionService->convert($payload, TypeDescriptor::createFromVariable($payload), MediaType::createApplicationXPHP(), TypeDescriptor::createArrayType(), MediaType::createApplicationXPHP()),
                $metadata,
                $this->eventMapper->mapEventToName($eventToConvert)
            );
        }

        return new ArrayIterator($proophEvents);
    }

    public function getWrappedEventStore(): LazyProophEventStore
    {
        return $this->eventStore;
    }

    public function getWrappedProophEventStore() : ProophEventStore
    {
        return $this->getWrappedEventStore()->getEventStore();
    }

    public function appendTo(string $streamName, array $streamEvents): void
    {
        $this->eventStore->appendTo(new StreamName($streamName), $this->convertProophEvents($streamEvents));
    }

    public function delete(string $streamName): void
    {
        $this->eventStore->delete(new StreamName($streamName));
    }

    public function fetchStreamMetadata(string $streamName): array
    {
        return $this->eventStore->fetchStreamMetadata(new StreamName($streamName));
    }

    public function hasStream(string $streamName): bool
    {
        return $this->eventStore->hasStream(new StreamName($streamName));
    }

    public function load(string $streamName, int $fromNumber = 1, int $count = null, MetadataMatcher $metadataMatcher = null, bool $deserialize = true): array
    {
        $streamEvents = $this->eventStore->load(new StreamName($streamName), $fromNumber, $count, $metadataMatcher);
        if (!$streamEvents->valid()) {
            $streamEvents = new ArrayIterator([]);
        }

        return $this->convertToEcotoneEvents(
            $streamEvents,
            $deserialize
        );
    }

    /**
     * @return Event[]
     */
    private function convertToEcotoneEvents(Iterator $streamEvents, bool $deserialize): array
    {
        $events = [];
        $sourcePHPType = TypeDescriptor::createArrayType();
        $PHPMediaType = MediaType::createApplicationXPHP();
        /** @var ProophMessage $event */
        while ($event = $streamEvents->current()) {
            $eventName = TypeDescriptor::create($this->eventMapper->mapNameToEventType($event->messageName()));
            $events[] = Event::createWithType(
                $eventName,
                $deserialize ? $this->conversionService->convert($event->payload(), $sourcePHPType, $PHPMediaType, $eventName, $PHPMediaType) : $event->payload(),
                $event->metadata()
            );

            $streamEvents->next();
        }

        return $events;
    }

    public function loadReverse(string $streamName, int $fromNumber = null, int $count = null, MetadataMatcher $metadataMatcher = null, bool $deserialize = true): array
    {
        $streamEvents = $this->eventStore->loadReverse(new StreamName($streamName), $fromNumber, $count, $metadataMatcher);
        if (!$streamEvents->valid()) {
            $streamEvents = new ArrayIterator([]);
        }

        return $this->convertToEcotoneEvents(
            $streamEvents,
            $deserialize
        );
    }

    public function fetchStreamNames(?string $filter, ?MetadataMatcher $metadataMatcher, int $limit = 20, int $offset = 0): array
    {
        return $this->eventStore->fetchStreamNames($filter, $metadataMatcher, $limit, $offset);
    }

    public function fetchStreamNamesRegex(string $filter, ?MetadataMatcher $metadataMatcher, int $limit = 20, int $offset = 0): array
    {
        return $this->eventStore->fetchStreamNamesRegex($filter, $metadataMatcher, $limit, $offset);
    }

    public function fetchCategoryNames(?string $filter, int $limit = 20, int $offset = 0): array
    {
        return $this->eventStore->fetchCategoryNames($filter, $limit, $offset);
    }

    public function fetchCategoryNamesRegex(string $filter, int $limit = 20, int $offset = 0): array
    {
        return $this->fetchCategoryNamesRegex($filter, $limit, $offset);
    }
}