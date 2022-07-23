<?php

namespace Ecotone\EventSourcing;

use Ecotone\Enqueue\OutboundMessageConverter;
use Ecotone\EventSourcing\Prooph\EcotoneEventStoreProophWrapper;
use Ecotone\EventSourcing\Prooph\LazyProophEventStore;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessageConverter\HeaderMapper;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Store\Document\DocumentStore;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\Attribute\AggregateVersion;
use Ecotone\Modelling\DistributedMetadata;
use Ecotone\Modelling\Event;
use Ecotone\Modelling\EventSourcedRepository;
use Ecotone\Modelling\EventStream;
use Ecotone\Modelling\SaveAggregateService;
use Ecotone\Modelling\SnapshotEvent;
use Prooph\EventStore\Exception\StreamNotFound;
use Prooph\EventStore\Metadata\MetadataMatcher;
use Prooph\EventStore\Metadata\Operator;
use Prooph\EventStore\StreamName;
use Ramsey\Uuid\Uuid;

class EventSourcingRepository implements EventSourcedRepository
{
    private HeaderMapper $headerMapper;
    private array $handledAggregateClassNames;
    private EcotoneEventStoreProophWrapper $eventStore;
    private EventSourcingConfiguration $eventSourcingConfiguration;
    private AggregateStreamMapping $aggregateStreamMapping;

    public function __construct(EcotoneEventStoreProophWrapper $eventStore, array $handledAggregateClassNames, HeaderMapper $headerMapper, EventSourcingConfiguration $eventSourcingConfiguration, AggregateStreamMapping $aggregateStreamMapping, private AggregateTypeMapping $aggregateTypeMapping, private array $snapshotedAggregates, private DocumentStore $documentStore)
    {
        $this->eventStore = $eventStore;
        $this->headerMapper = $headerMapper;
        $this->handledAggregateClassNames = $handledAggregateClassNames;
        $this->eventSourcingConfiguration = $eventSourcingConfiguration;
        $this->aggregateStreamMapping = $aggregateStreamMapping;
    }

    public function canHandle(string $aggregateClassName): bool
    {
        return in_array($aggregateClassName, $this->handledAggregateClassNames);
    }

    public function findBy(string $aggregateClassName, array $identifiers): EventStream
    {
        $aggregateId = reset($identifiers);
        $aggregateVersion = 0;
        $streamName = $this->getStreamName($aggregateClassName, $aggregateId);
        $aggregateType = $this->getAggregateType($aggregateClassName);
        $snapshotEvent = [];

        if (in_array($aggregateClassName, $this->snapshotedAggregates)) {
            $aggregate = $this->documentStore->findDocument(SaveAggregateService::getSnapshotCollectionName($aggregateClassName), $aggregateId);

            if (! is_null($aggregate)) {
                $aggregateVersion = $this->getAggregateVersion($aggregate);
                Assert::isTrue($aggregateVersion > 0, sprintf('Serialization for snapshot of %s is set incorrectly, it does not serialize aggregate version', $aggregate::class));

                $snapshotEvent[] = new SnapshotEvent($aggregate);
            }
        }

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch(
            LazyProophEventStore::AGGREGATE_TYPE,
            Operator::EQUALS(),
            $aggregateType
        );
        $metadataMatcher = $metadataMatcher->withMetadataMatch(
            LazyProophEventStore::AGGREGATE_ID,
            Operator::EQUALS(),
            $aggregateId
        );

        if ($aggregateVersion > 0) {
            $metadataMatcher = $metadataMatcher->withMetadataMatch(
                LazyProophEventStore::AGGREGATE_VERSION,
                Operator::GREATER_THAN(),
                $aggregateVersion
            );
        }

        try {
            $streamEvents = $this->eventStore->load($streamName, 1, null, $metadataMatcher);
        } catch (StreamNotFound) {
            return EventStream::createEmpty();
        }

        if (! empty($streamEvents)) {
            $aggregateVersion = $streamEvents[array_key_last($streamEvents)]->getMetadata()[LazyProophEventStore::AGGREGATE_VERSION];
        }

        return EventStream::createWith($aggregateVersion, array_merge($snapshotEvent, $streamEvents));
    }

    public function save(array $identifiers, string $aggregateClassName, array $events, array $metadata, int $versionBeforeHandling): void
    {
        $aggregateId = reset($identifiers);
        Assert::notNullAndEmpty($aggregateId, sprintf('There was a problem when retrieving identifier for %s', $aggregateClassName));

        $streamName = $this->getStreamName($aggregateClassName, $aggregateId);
        $aggregateType = $this->getAggregateType($aggregateClassName);
        $metadata = OutboundMessageConverter::unsetEnqueueMetadata($metadata);
        $metadata = DistributedMetadata::unsetDistributionKeys($metadata);

        $eventsWithMetadata = [];
        $eventsCount = count($events);
        for ($eventNumber = 1; $eventNumber <= $eventsCount; $eventNumber++) {
            $event = $events[$eventNumber - 1];
            $eventsWithMetadata[] = Event::create(
                $event,
                array_merge(
                    $this->headerMapper->mapFromMessageHeaders($metadata),
                    [
                        MessageHeaders::MESSAGE_ID => Uuid::uuid4()->toString(),
                        LazyProophEventStore::AGGREGATE_ID => $aggregateId,
                        LazyProophEventStore::AGGREGATE_TYPE => $aggregateType,
                        LazyProophEventStore::AGGREGATE_VERSION => $versionBeforeHandling + $eventNumber,
                    ]
                )
            );
        }
        $this->eventStore->appendTo($streamName, $eventsWithMetadata);
    }

    private function getStreamName(string $aggregateClassName, mixed $aggregateId): StreamName
    {
        $streamName = $aggregateClassName;
        if (array_key_exists($aggregateClassName, $this->aggregateStreamMapping->getAggregateToStreamMapping())) {
            $streamName =  $this->aggregateStreamMapping->getAggregateToStreamMapping()[$aggregateClassName];
        }

        if ($this->eventSourcingConfiguration->isUsingAggregateStreamStrategy()) {
            $streamName = $streamName . '-' . $aggregateId;
        }

        return new StreamName($streamName);
    }

    private function getAggregateType(string $aggregateClassName): string
    {
        if (array_key_exists($aggregateClassName, $this->aggregateTypeMapping->getAggregateTypeMapping())) {
            return $this->aggregateTypeMapping->getAggregateTypeMapping()[$aggregateClassName];
        }

        return $aggregateClassName;
    }

    private function getAggregateVersion(object|array|string $aggregate): mixed
    {
        $propertyReader = new PropertyReaderAccessor();
        $versionAnnotation = TypeDescriptor::create(AggregateVersion::class);
        $aggregateVersionPropertyName = null;
        foreach (ClassDefinition::createFor(TypeDescriptor::createFromVariable($aggregate))->getProperties() as $property) {
            if ($property->hasAnnotation($versionAnnotation)) {
                $aggregateVersionPropertyName = $property->getName();
                break;
            }
        }

        $aggregateVersion = $propertyReader->getPropertyValue(
            PropertyPath::createWith($aggregateVersionPropertyName),
            $aggregate
        );
        return $aggregateVersion;
    }
}
