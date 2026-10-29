<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\EventSourcing;

use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Store\Document\DocumentException;
use Ecotone\Messaging\Store\Document\DocumentStore;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\GenericMessage;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateDefinitionRegistry;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\ResolvedAggregate;
use Ecotone\Modelling\Attribute\AggregateVersion;
use Ecotone\Modelling\BaseEventSourcingConfiguration;
use Ecotone\Modelling\EventSourcedRepository;
use Ecotone\Modelling\EventSourcingExecutor\GroupedEventSourcingExecutor;
use Ecotone\Modelling\Repository\AggregateRepository;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class EventSourcedRepositoryAdapter implements AggregateRepository
{
    public const SNAPSHOT_COLLECTION = 'aggregate_snapshots_';

    public function __construct(
        private EventSourcedRepository $eventSourcedRepository,
        private AggregateDefinitionRegistry $aggregateDefinitionRegistry,
        private BaseEventSourcingConfiguration $eventSourcingConfiguration,
        private GroupedEventSourcingExecutor $eventSourcingHandlerExecutor,
        private ContainerInterface $container,
        private PropertyEditorAccessor $propertyEditorAccessor,
        private bool $isDefaultRepository,
        private LoggerInterface $logger,
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
            $aggregate = $this->eventSourcingHandlerExecutor->fillFor($aggregateClassName, $aggregate, $eventStream->getEvents());
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
                GenericMessage::createWithEmptyHeaders(''),
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
                    $documentStore->upsertDocument(self::getSnapshotCollectionName($aggregate->getAggregateClassName()), self::getSnapshotDocumentId($aggregate->getIdentifiers()), $aggregate->getAggregateInstance());
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

    private function getAggregateVersion(object|array|string $aggregate): mixed
    {
        $propertyReader = new PropertyReaderAccessor();
        $versionAnnotation = Type::attribute(AggregateVersion::class);
        $aggregateVersionPropertyName = null;
        foreach (ClassDefinition::createFor(Type::createFromVariable($aggregate))->getProperties() as $property) {
            if ($property->hasAnnotation($versionAnnotation)) {
                $aggregateVersionPropertyName = $property->getName();
                break;
            }
        }

        return $propertyReader->getPropertyValue(
            PropertyPath::createWith($aggregateVersionPropertyName),
            $aggregate
        );
    }

    private static function getSnapshotCollectionName(string $aggregateClassname): string
    {
        return self::SNAPSHOT_COLLECTION . $aggregateClassname;
    }

    private static function getSnapshotDocumentId(array $identifiers): string
    {
        return count($identifiers) === 1 ? (string)reset($identifiers) : json_encode($identifiers);
    }
}
