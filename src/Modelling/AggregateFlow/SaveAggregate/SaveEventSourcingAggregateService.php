<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\SaveAggregate;

use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Store\Document\DocumentStore;
use Ecotone\Messaging\Store\Document\InMemoryDocumentStore;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\EventSourcedRepository;
use Ecotone\Modelling\SaveAggregateService;

/**
 * licence Apache-2.0
 */
final class SaveEventSourcingAggregateService implements SaveAggregateService
{
    public const NO_SNAPSHOT_THRESHOLD = 0;
    public const SNAPSHOT_COLLECTION = 'aggregate_snapshots_';

    public function __construct(
        private string                 $calledClass,
        private string                 $aggregateClassName,
        private bool                   $isFactoryMethod,
        private EventSourcedRepository $aggregateRepository,
        private PropertyEditorAccessor $propertyEditorAccessor,
        private PropertyReaderAccessor $propertyReaderAccessor,
        private array                  $aggregateIdentifierMapping,
        private array                  $aggregateIdentifierGetMethods,
        private ?string                $aggregateVersionProperty,
        private bool                   $isAggregateVersionAutomaticallyIncreased,
        private bool                   $useSnapshot,
        private int $snapshotTriggerThreshold,
        private ?DocumentStore $documentStore,
    ) {
        $this->documentStore ??= InMemoryDocumentStore::createEmpty();
    }

    public function save(Message $message, array $metadata): Message
    {
        $events = $this->resolveEvents($message, $metadata, $this->calledClass);
        if ($events === []) {
            return MessageBuilder::fromMessage($message)->build();
        }

        $aggregate = SaveAggregateServiceTemplate::resolveAggregate($this->calledClass, $message, $this->isFactoryMethod);
        $versionBeforeHandling = SaveAggregateServiceTemplate::resolveVersionBeforeHandling($message);
        SaveAggregateServiceTemplate::enrichVersionIfNeeded(
            $this->propertyEditorAccessor,
            $versionBeforeHandling,
            $aggregate,
            $message,
            $this->aggregateVersionProperty,
            $this->isAggregateVersionAutomaticallyIncreased
        );

        $aggregateIds = $this->getAggregateIds($metadata, $aggregate);
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

        $this->aggregateRepository->save($aggregateIds, $this->aggregateClassName, $events, MessageHeaders::unsetNonUserKeys($metadata), $versionBeforeHandling);

        return SaveAggregateServiceTemplate::buildReplyMessage(
            $this->isFactoryMethod,
            $aggregateIds,
            $message,
        );
    }

    public static function getSnapshotCollectionName(string $aggregateClassname): string
    {
        return self::SNAPSHOT_COLLECTION . $aggregateClassname;
    }

    public function resolveEvents(Message $message, array $metadata, string $calledInterface): array
    {
        if ($this->isFactoryMethod) {
            $events = $message->getHeaders()->containsKey(AggregateMessage::RESULT_AGGREGATE_EVENTS) ? $message->getHeaders()->get(AggregateMessage::RESULT_AGGREGATE_EVENTS) : [];
        } else {
            $events = $message->getHeaders()->containsKey(AggregateMessage::CALLED_AGGREGATE_EVENTS) ? $message->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_EVENTS) : [];
        }

        return SaveAggregateServiceTemplate::buildEcotoneEvents($events, $calledInterface, $message, $metadata);
    }

    private function getAggregateIds(array $metadata, object|string $aggregate): array
    {
        return SaveAggregateServiceTemplate::getAggregateIds(
            $this->propertyReaderAccessor,
            $metadata,
            $this->calledClass,
            $this->aggregateIdentifierMapping,
            $this->aggregateIdentifierGetMethods,
            $aggregate,
            true
        );
    }
}
