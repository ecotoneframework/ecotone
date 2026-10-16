<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\EventSourcing;

use Ecotone\Api\Gateway\DocumentStore;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Store\Document\DocumentException;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateDefinitionRegistry;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\ResolvedAggregate;
use Ecotone\Modelling\BaseEventSourcingConfiguration;
use Ecotone\Modelling\EventSourcedRepository;
use Ecotone\Modelling\EventSourcingExecutor\GroupedEventSourcingExecutor;
use Ecotone\Modelling\Repository\AggregateRepository;

use function is_string;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class EventSourcedRepositoryAdapter implements AggregateRepository
{
    public const SNAPSHOT_COLLECTION = 'aggregate_snapshots_';
    public const SNAPSHOT_FOLD_SHAPE_COLLECTION = 'aggregate_snapshot_fold_shapes_';

    public function __construct(
        private EventSourcedRepository $eventSourcedRepository,
        private AggregateDefinitionRegistry $aggregateDefinitionRegistry,
        private BaseEventSourcingConfiguration $eventSourcingConfiguration,
        private GroupedEventSourcingExecutor $applyMethodExecutor,
        private ContainerInterface $container,
        private PropertyEditorAccessor $propertyEditorAccessor,
        private bool $isDefaultRepository,
        private LoggerInterface $logger,
        private MessageBuilder $messageBuilder,
    ) {
    }

    public function canHandle(string $aggregateClassName): bool
    {
        if ($this->isDefaultRepository && $this->aggregateDefinitionRegistry->has($aggregateClassName)) {
            $aggregateDefinition = $this->aggregateDefinitionRegistry->getFor($aggregateClassName);
            return $aggregateDefinition->isEventSourced();
        }
        return $this->eventSourcedRepository->canHandle($aggregateClassName);
    }

    public function findBy(string $aggregateClassName, array $identifiers): ?ResolvedAggregate
    {
        $aggregate = null;
        $aggregateVersion = null;
        foreach ($this->eventSourcingConfiguration->getSnapshotsConfig() as $aggregateClass => $config) {
            if ($aggregateClass === $aggregateClassName) {
                /** @var DocumentStore $documentStore */
                $documentStore = $this->container->get($config['documentStore']);

                try {
                    $aggregate = $documentStore->findDocument(self::getSnapshotCollectionName($aggregateClassName), self::getSnapshotDocumentId($identifiers));
                } catch (DocumentException $documentException) {
                    $this->logger->error("Failure during loading snapshot for aggregate {$aggregateClassName} with identifiers " . json_encode($identifiers) . '. Snapshot ignored to self system system. Error: ' . $documentException->getMessage(), [
                        'exception' => $documentException,
                    ]);
                }

                if ($aggregate !== null && ! $this->foldShapeStoredWith($documentStore, $aggregateClassName, $identifiers)) {
                    $this->logger->error("Snapshot for aggregate {$aggregateClassName} with identifiers " . json_encode($identifiers) . ' was taken with a different set of #[Apply] events than the class declares now. Snapshot ignored to self-heal system.');
                    $aggregate = null;
                }

                if ($aggregate !== null && $aggregate::class === $aggregateClassName) {
                    $aggregateVersion = $this->getAggregateVersion($aggregate);
                    Assert::isTrue($aggregateVersion > 0, sprintf('Serialization for snapshot of %s is set incorrectly, it does not serialize aggregate version', $aggregate::class));
                } elseif ($aggregate !== null) {
                    $this->logger->error("Snapshot for aggregate {$aggregateClassName} was found, but it is not instance of {$aggregateClassName}. It is type of " . gettype($aggregate) . '. Snapshot ignored to self-heal system.');
                    $aggregate = null;
                }
            }
        }

        $eventStream = $this->eventSourcedRepository->findBy($aggregateClassName, $identifiers, $aggregate === null ? 1 : ($aggregateVersion + 1));

        if ($eventStream->getEvents()) {
            $aggregateVersion = $eventStream->getAggregateVersion();
            $aggregate = $this->applyMethodExecutor->fillFor($aggregateClassName, $aggregate, $eventStream->getEvents());
        }

        if (! $aggregate) {
            return null;
        }

        $aggregateClassDefinition = $this->aggregateDefinitionRegistry->getFor($aggregateClassName);
        if (! is_null($aggregateVersion) && $aggregateClassDefinition->isAggregateVersionAutomaticallyIncreased()) {
            $this->propertyEditorAccessor->enrichDataWith(
                PropertyPath::createWith($aggregateClassDefinition->getAggregateVersionProperty()),
                $aggregate,
                $aggregateVersion,
                $this->messageBuilder->withPayload('')->build(),
                null
            );
        }

        return new ResolvedAggregate(
            $aggregateClassDefinition,
            false,
            $aggregate,
            $aggregateVersion,
            $identifiers,
            [],
        );
    }

    public function save(ResolvedAggregate $aggregate, array $metadata): int
    {
        $version = $aggregate->getVersionBeforeHandling();

        if ($this->eventSourcingConfiguration->useSnapshotFor($aggregate->getAggregateClassName())) {
            $snapshotTriggerThreshold = $this->eventSourcingConfiguration->getSnapshotTriggerThresholdFor($aggregate->getAggregateClassName());
            foreach ($aggregate->getEvents() as $event) {
                $version += 1;
                if ($version % $snapshotTriggerThreshold === 0) {
                    $documentStore = $this->container->get(
                        $this->eventSourcingConfiguration->getDocumentStoreReferenceFor($aggregate->getAggregateClassName())
                    );
                    $documentStore->upsertDocument(self::getSnapshotCollectionName($aggregate->getAggregateClassName()), self::getSnapshotDocumentId($aggregate->getIdentifiers()), $aggregate->getAggregateInstance(), DocumentStore::LAST_WRITE_WINS);
                    $documentStore->upsertDocument(
                        self::getSnapshotFoldShapeCollectionName($aggregate->getAggregateClassName()),
                        self::getSnapshotDocumentId($aggregate->getIdentifiers()),
                        json_encode(['foldShape' => $this->applyMethodExecutor->foldShapeOf($aggregate->getAggregateClassName())]),
                        DocumentStore::LAST_WRITE_WINS,
                    );
                }
            }
        }

        $this->eventSourcedRepository->save(
            $aggregate->getIdentifiers(),
            $aggregate->getAggregateClassDefinition()->getClassName(),
            $aggregate->getEvents(),
            $metadata,
            $aggregate->getVersionBeforeHandling()
        );

        return $version + count($aggregate->getEvents());
    }

    /**
     * A snapshot is only loadable while the class still folds the events it was taken with. A
     * document stored before Ecotone recorded the fold shape has no marker beside it, so it counts
     * as taken with another shape and is replaced the next time a snapshot is written.
     */
    private function foldShapeStoredWith(DocumentStore $documentStore, string $aggregateClassName, array $identifiers): bool
    {
        try {
            $marker = $documentStore->findDocument(self::getSnapshotFoldShapeCollectionName($aggregateClassName), self::getSnapshotDocumentId($identifiers));
        } catch (DocumentException) {
            return false;
        }

        if (! is_string($marker)) {
            return false;
        }

        return (json_decode($marker, true)['foldShape'] ?? null) === $this->applyMethodExecutor->foldShapeOf($aggregateClassName);
    }

    private function getAggregateVersion(object $aggregate): mixed
    {
        return (new PropertyReaderAccessor())->getPropertyValue(
            PropertyPath::createWith($this->aggregateDefinitionRegistry->getFor($aggregate::class)->getAggregateVersionProperty()),
            $aggregate
        );
    }

    private static function getSnapshotCollectionName(string $aggregateClassname): string
    {
        return self::SNAPSHOT_COLLECTION . $aggregateClassname;
    }

    private static function getSnapshotFoldShapeCollectionName(string $aggregateClassname): string
    {
        return self::SNAPSHOT_FOLD_SHAPE_COLLECTION . $aggregateClassname;
    }

    private static function getSnapshotDocumentId(array $identifiers): string
    {
        return count($identifiers) === 1 ? (string)reset($identifiers) : json_encode($identifiers);
    }
}
