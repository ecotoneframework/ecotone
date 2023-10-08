<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Metadata\RevisionMetadataEnricher;
use Ecotone\Messaging\Store\Document\DocumentStore;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\Attribute\NamedEvent;
use Ramsey\Uuid\Uuid;

/**
 * Class SaveAggregateService
 * @package Ecotone\Modelling
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SaveAggregateService
{
    public const NO_SNAPSHOT_THRESHOLD = 0;
    public const SNAPSHOT_COLLECTION = 'aggregate_snapshots_';

    private StandardRepository|EventSourcedRepository $aggregateRepository;
    private PropertyReaderAccessor $propertyReaderAccessor;
    private EventBus $eventBus;
    private ?string $aggregateMethodWithEvents;
    private PropertyEditorAccessor $propertyEditorAccessor;
    private array $aggregateIdentifierMapping;
    private InterfaceToCall|string $aggregateInterface;
    private ?string $aggregateVersionProperty;
    private bool $isAggregateVersionAutomaticallyIncreased;
    private bool $isEventSourced;
    private bool $isFactoryMethod;
    private array $aggregateIdentifierGetMethods;

    public function __construct(
        InterfaceToCall|string                    $aggregateInterface,
        bool $isFactoryMethod,
        bool $isEventSourced,
        StandardRepository|EventSourcedRepository $aggregateRepository,
        PropertyEditorAccessor $propertyEditorAccessor,
        PropertyReaderAccessor                    $propertyReaderAccessor,
        EventBus $eventBus,
        ?string                                   $aggregateMethodWithEvents,
        array $aggregateIdentifierMapping,
        array $aggregateIdentifierGetMethods,
        ?string $aggregateVersionProperty,
        bool $isAggregateVersionAutomaticallyIncreased,
        private bool $useSnapshot,
        private int $snapshotTriggerThreshold,
        private DocumentStore $documentStore
    ) {
        $this->aggregateRepository = $aggregateRepository;
        $this->propertyReaderAccessor = $propertyReaderAccessor;
        $this->eventBus = $eventBus;
        $this->propertyEditorAccessor = $propertyEditorAccessor;
        $this->aggregateMethodWithEvents = $aggregateMethodWithEvents;
        $this->aggregateIdentifierMapping = $aggregateIdentifierMapping;
        $this->aggregateInterface = $aggregateInterface;
        $this->aggregateVersionProperty = $aggregateVersionProperty;
        $this->isAggregateVersionAutomaticallyIncreased = $isAggregateVersionAutomaticallyIncreased;
        $this->isEventSourced = $isEventSourced;
        $this->isFactoryMethod = $isFactoryMethod;
        $this->aggregateIdentifierGetMethods = $aggregateIdentifierGetMethods;
    }

    public function save(Message $message, array $metadata): Message
    {
        $metadata = MessageHeaders::unsetNonUserKeys($metadata);

        $events = $this->resolveEvents($message, $metadata);

        if ($this->isEventSourced && $events === []) {
            return MessageBuilder::fromMessage($message)->build();
        }

        $versionBeforeHandling = $message->getHeaders()->containsKey(AggregateMessage::TARGET_VERSION)
            ? $message->getHeaders()->get(AggregateMessage::TARGET_VERSION)
            : null;

        $aggregate = $message->getHeaders()->get(AggregateMessage::AGGREGATE_OBJECT);
        if ($this->aggregateVersionProperty && $this->isAggregateVersionAutomaticallyIncreased) {
            $this->propertyEditorAccessor->enrichDataWith(
                PropertyPath::createWith($this->aggregateVersionProperty),
                $aggregate,
                $versionBeforeHandling + 1,
                $message,
                null
            );
        }

        $aggregateIds = $message->getHeaders()->containsKey(AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER)
                            ? $message->getHeaders()->get(AggregateMessage::AGGREGATE_ID)
                            : [];
        $aggregateIds = $aggregateIds ?: $this->getAggregateIds($aggregateIds, $aggregate, $this->isEventSourced);

        if ($this->isEventSourced) {
            if ($this->useSnapshot && is_object($aggregate)) {
                $version = $versionBeforeHandling;
                foreach ($events as $event) {
                    $version += 1;

                    if ($version % $this->snapshotTriggerThreshold === 0) {
                        Assert::isTrue(count($aggregateIds) === 1, 'Snapshoting is possible only for aggregates having single identifiers');

                        $this->documentStore->upsertDocument(self::getSnapshotCollectionName($aggregate::class), reset($aggregateIds), $aggregate);
                    }
                }
            }

            $this->aggregateRepository->save($aggregateIds, is_string($this->aggregateInterface) ? $this->aggregateInterface : $this->aggregateInterface->getInterfaceName(), $events, $metadata, $versionBeforeHandling);
        } else {
            $this->aggregateRepository->save($aggregateIds, $aggregate, $metadata, $versionBeforeHandling);
        }

        $aggregateIds = $aggregateIds ?: $this->getAggregateIds($aggregateIds, $aggregate, true);
        if ($this->isFactoryMethod()) {
            if (count($aggregateIds) === 1) {
                $aggregateIds = reset($aggregateIds);
            }

            $message =
                MessageBuilder::fromMessage($message)
                    ->setPayload($aggregateIds)
                    ->build();
        }

        foreach ($events as $event) {
            $this->eventBus->publish($event->getPayload(), $event->getMetadata());

            $eventDefinition = ClassDefinition::createFor(TypeDescriptor::createFromVariable($event->getPayload()));
            $namedEvent = TypeDescriptor::create(NamedEvent::class);
            if ($eventDefinition->hasClassAnnotation($namedEvent)) {
                /** @var NamedEvent $namedEvent */
                $namedEvent = $eventDefinition->getSingleClassAnnotation($namedEvent);

                $this->eventBus->publishWithRouting($namedEvent->getName(), $event->getPayload(), MediaType::APPLICATION_X_PHP, $event->getMetadata());
            }
        }

        return MessageBuilder::fromMessage($message)
                ->build();
    }

    public static function getSnapshotCollectionName(string $aggregateClassname): string
    {
        return self::SNAPSHOT_COLLECTION . $aggregateClassname;
    }

    private function isFactoryMethod(): bool
    {
        return $this->isFactoryMethod;
    }

    private function getAggregateIds(array $aggregateIds, object $aggregate, bool $throwOnNoIdentifier): array
    {
        foreach ($this->aggregateIdentifierMapping as $aggregateIdName => $aggregateIdValue) {
            if (isset($this->aggregateIdentifierGetMethods[$aggregateIdName])) {
                $id = call_user_func([$aggregate, $this->aggregateIdentifierGetMethods[$aggregateIdName]]);

                if (! is_null($id)) {
                    $aggregateIds[$aggregateIdName] = $id;
                }

                continue;
            }

            $id = $this->propertyReaderAccessor->hasPropertyValue(PropertyPath::createWith($aggregateIdName), $aggregate)
                ? $this->propertyReaderAccessor->getPropertyValue(PropertyPath::createWith($aggregateIdName), $aggregate)
                : null;

            if (! $id) {
                if (! $throwOnNoIdentifier) {
                    continue;
                }

                throw NoCorrectIdentifierDefinedException::create("After calling {$this->aggregateInterface} has no identifier assigned. If you're using Event Sourcing Aggregate, please set up #[EventSourcingHandler] that will assign the id after first event");
            }

            $aggregateIds[$aggregateIdName] = $id;
        }

        return AggregateIdResolver::resolveArrayOfIdentifiers(get_class($aggregate), $aggregateIds);
    }

    /**
     * @return array<Event>
     */
    private function resolveEvents(Message $message, array $metadata): array
    {
        $events = [];

        if ($this->isEventSourced) {
            $events = $message->getPayload();
            Assert::isIterable($events, "Return value Event Sourced Aggregate {$this->aggregateInterface} must return array of events");
        }

        if ($events === [] && $this->aggregateMethodWithEvents) {
            $aggregate = $message->getHeaders()->get(AggregateMessage::AGGREGATE_OBJECT);
            $events = call_user_func([$aggregate, $this->aggregateMethodWithEvents]);
        }

        return array_map(function ($event) use ($message, $metadata): Event {
            if (! is_object($event)) {
                $typeDescriptor = TypeDescriptor::createFromVariable($event);
                throw InvalidArgumentException::create("Events return by after calling {$this->aggregateInterface} must all be objects, {$typeDescriptor->toString()} given");
            }
            if ($event instanceof Event) {
                $metadata = $event->getMetadata();
                $event = $event->getPayload();
            }

            $metadata = MessageHeaders::unsetAllFrameworkHeaders($metadata);
            $metadata = RevisionMetadataEnricher::enrich($metadata, $event);
            $metadata[MessageHeaders::MESSAGE_ID] ??= Uuid::uuid4()->toString();
            $metadata[MessageHeaders::TIMESTAMP] ??= (int)round(microtime(true));
            $metadata = MessageHeaders::propagateContextHeaders([
                MessageHeaders::MESSAGE_ID => $message->getHeaders()->getMessageId(),
                MessageHeaders::MESSAGE_CORRELATION_ID => $message->getHeaders()->getCorrelationId(),
            ], $metadata);

            return Event::create($event, $metadata);
        }, $events);
    }
}
