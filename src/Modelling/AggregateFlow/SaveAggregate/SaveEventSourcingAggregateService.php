<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\SaveAggregate;

use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Metadata\RevisionMetadataEnricher;
use Ecotone\Messaging\Store\Document\DocumentStore;
use Ecotone\Messaging\Store\Document\InMemoryDocumentStore;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateIdResolver;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\Event;
use Ecotone\Modelling\EventSourcedRepository;
use Ecotone\Modelling\NoAggregateFoundToBeSaved;
use Ecotone\Modelling\NoCorrectIdentifierDefinedException;
use Ecotone\Modelling\SaveAggregateService;
use Ramsey\Uuid\Uuid;

final class SaveEventSourcingAggregateService implements SaveAggregateService
{
    public const NO_SNAPSHOT_THRESHOLD = 0;
    public const SNAPSHOT_COLLECTION = 'aggregate_snapshots_';

    public function __construct(
        private string $calledInterface,
        private string $aggregateClassName,
        private bool $isFactoryMethod,
        private EventSourcedRepository $aggregateRepository,
        private PropertyEditorAccessor $propertyEditorAccessor,
        private PropertyReaderAccessor $propertyReaderAccessor,
        private array $aggregateIdentifierMapping,
        private array $aggregateIdentifierGetMethods,
        private ?string $aggregateVersionProperty,
        private bool $isAggregateVersionAutomaticallyIncreased,
        private bool $useSnapshot,
        private int $snapshotTriggerThreshold,
        private ?DocumentStore $documentStore,
    ) {
        $this->documentStore ??= InMemoryDocumentStore::createEmpty();
    }

    public function save(Message $message, array $metadata): Message
    {
        $metadata = MessageHeaders::unsetNonUserKeys($metadata);

        $events = $this->resolveEvents($message, $metadata, $this->calledInterface);
        if ($events === []) {
            return MessageBuilder::fromMessage($message)->build();
        }

        $aggregate = $this->resolveAggregate($message);
        $versionBeforeHandling = $message->getHeaders()->containsKey(AggregateMessage::TARGET_VERSION) ? $message->getHeaders()->get(AggregateMessage::TARGET_VERSION) : 0;
        if ($this->aggregateVersionProperty && $this->isAggregateVersionAutomaticallyIncreased) {
            $this->propertyEditorAccessor->enrichDataWith(
                PropertyPath::createWith($this->aggregateVersionProperty),
                $aggregate,
                $versionBeforeHandling + 1,
                $message,
                null
            );
        }

        $aggregateIds = $message->getHeaders()->containsKey(AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER) ? $message->getHeaders()->get(AggregateMessage::AGGREGATE_ID) : [];
        $aggregateIds = $aggregateIds ?: $this->getAggregateIds($aggregateIds, $aggregate);

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

        $this->aggregateRepository->save($aggregateIds, $this->aggregateClassName, $events, $metadata, $versionBeforeHandling);

        $aggregateIds = $aggregateIds ?: $this->getAggregateIds($aggregateIds, $aggregate);
        if ($this->isFactoryMethod) {
            if (count($aggregateIds) === 1) {
                $aggregateIds = reset($aggregateIds);
            }

            $message = MessageBuilder::fromMessage($message)
                ->setPayload($aggregateIds)
                ->build()
            ;
        }

        return MessageBuilder::fromMessage($message)->build();
    }

    public static function getSnapshotCollectionName(string $aggregateClassname): string
    {
        return self::SNAPSHOT_COLLECTION . $aggregateClassname;
    }

    private function getAggregateIds(array $aggregateIds, object $aggregate): array
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
                throw NoCorrectIdentifierDefinedException::create("After calling {$this->calledInterface} has no identifier assigned. If you're using Event Sourcing Aggregate, please set up #[EventSourcingHandler] that will assign the id after first event");
            }

            $aggregateIds[$aggregateIdName] = $id;
        }

        return AggregateIdResolver::resolveArrayOfIdentifiers(get_class($aggregate), $aggregateIds);
    }

    private function resolveAggregate(Message $message): object|string
    {
        $messageHeaders = $message->getHeaders();
        if ($this->isFactoryMethod && $messageHeaders->containsKey(AggregateMessage::RESULT_AGGREGATE_OBJECT)) {
            return $messageHeaders->get(AggregateMessage::RESULT_AGGREGATE_OBJECT);
        }
        if ($messageHeaders->containsKey(AggregateMessage::CALLED_AGGREGATE_OBJECT)) {
            return $messageHeaders->get(AggregateMessage::CALLED_AGGREGATE_OBJECT);
        }

        throw NoAggregateFoundToBeSaved::create("After calling {$this->calledInterface} no aggregate was found to be saved.");
    }

    public function resolveEvents(Message $message, array $metadata, string $calledInterface): array
    {
        if ($this->isFactoryMethod) {
            $events = $message->getHeaders()->containsKey(AggregateMessage::RESULT_AGGREGATE_EVENTS) ? $message->getHeaders()->get(AggregateMessage::RESULT_AGGREGATE_EVENTS) : [];
        } else {
            $events = $message->getHeaders()->containsKey(AggregateMessage::CALLED_AGGREGATE_EVENTS) ? $message->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_EVENTS) : [];
        }

        Assert::isIterable($events, "Return value Event Sourced Aggregate {$calledInterface} must return array of events");

        return array_map(static function ($event) use ($message, $metadata, $calledInterface): Event {
            if (! is_object($event)) {
                $typeDescriptor = TypeDescriptor::createFromVariable($event);
                throw InvalidArgumentException::create("Events return by after calling {$calledInterface} must all be objects, {$typeDescriptor->toString()} given");
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
