<?php

namespace Ecotone\EventSourcing;

use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Prooph\EventStore\Pdo\Projection\MariaDbProjectionManager;
use Prooph\EventStore\Pdo\Projection\MySqlProjectionManager;
use Prooph\EventStore\Pdo\Projection\PostgresProjectionManager;
use Prooph\EventStore\Projection\ProjectionManager;
use Prooph\EventStore\Projection\ProjectionStatus;
use Prooph\EventStore\Projection\Projector;
use Prooph\EventStore\Projection\Query;
use Prooph\EventStore\Projection\ReadModel;
use Prooph\EventStore\Projection\ReadModelProjector;

class LazyProophProjectionManager implements ProjectionManager
{
    private ?ProjectionManager $lazyInitializedProjectionManager = null;

    /**
     * @param ProjectionSetupConfiguration[] $projectionSetupConfigurations
     */
    public function __construct(
        private EventSourcingConfiguration $eventSourcingConfiguration,
        private array $projectionSetupConfigurations,
        private ReferenceSearchService $referenceSearchService
    ) {
    }

    private function getProjectionManager(): ProjectionManager
    {
        if ($this->lazyInitializedProjectionManager) {
            return $this->lazyInitializedProjectionManager;
        }

        $eventStore = $this->getLazyProophEventStore();

        $this->lazyInitializedProjectionManager = match ($eventStore->getEventStoreType()) {
            LazyProophEventStore::EVENT_STORE_TYPE_POSTGRES => new PostgresProjectionManager($eventStore->getEventStore(), $eventStore->getWrappedConnection(), $this->eventSourcingConfiguration->getEventStreamTableName(), $this->eventSourcingConfiguration->getProjectionsTable()),
            LazyProophEventStore::EVENT_STORE_TYPE_MYSQL => new MySqlProjectionManager($eventStore->getEventStore(), $eventStore->getWrappedConnection(), $this->eventSourcingConfiguration->getEventStreamTableName(), $this->eventSourcingConfiguration->getProjectionsTable()),
            LazyProophEventStore::EVENT_STORE_TYPE_MARIADB => new MariaDbProjectionManager($eventStore->getEventStore(), $eventStore->getWrappedConnection(), $this->eventSourcingConfiguration->getEventStreamTableName(), $this->eventSourcingConfiguration->getProjectionsTable()),
            LazyProophEventStore::EVENT_STORE_TYPE_IN_MEMORY => $this->eventSourcingConfiguration->getInMemoryProjectionManager()
        };

        return $this->lazyInitializedProjectionManager;
    }

    public function ensureEventStoreIsPrepared(): void
    {
        $this->getLazyProophEventStore()->prepareEventStore();
    }

    public function createQuery(): Query
    {
        return $this->getProjectionManager()->createQuery();
    }

    public function createProjection(string $name, array $options = []): Projector
    {
        return $this->getProjectionManager()->createProjection($name, $options);
    }

    public function createReadModelProjection(string $name, ReadModel $readModel, array $options = []): ReadModelProjector
    {
        return $this->getProjectionManager()->createReadModelProjection($name, $readModel, $options);
    }

    public function deleteProjection(string $name, bool $deleteEmittedEvents): void
    {
        $this->getProjectionManager()->deleteProjection($name, $deleteEmittedEvents);
        $this->triggerActionOnProjection($name);
    }

    public function resetProjection(string $name): void
    {
        $this->getProjectionManager()->resetProjection($name);
        $this->triggerActionOnProjection($name);
    }

    public function initializeProjection(string $name): void
    {
        /** @var MessagingEntrypoint $messagingEntrypoint */
        $messagingEntrypoint = $this->referenceSearchService->get(MessagingEntrypoint::class);
        $messagingEntrypoint->send([], $this->projectionSetupConfigurations[$name]->getInitializationChannelName());
        $this->triggerActionOnProjection($name);
    }

    public function stopProjection(string $name): void
    {
        $this->getProjectionManager()->stopProjection($name);

        /** @var MessagingEntrypoint $messagingEntrypoint */
        $this->triggerActionOnProjection($name);
    }

    public function fetchProjectionNames(?string $filter, int $limit = 20, int $offset = 0): array
    {
        $this->ensureEventStoreIsPrepared();
        ;

        return $this->getProjectionManager()->fetchProjectionNames($filter, $limit, $offset);
    }

    public function fetchProjectionNamesRegex(string $regex, int $limit = 20, int $offset = 0): array
    {
        $this->ensureEventStoreIsPrepared();
        ;

        return $this->getProjectionManager()->fetchProjectionNamesRegex($regex, $limit, $offset);
    }

    public function fetchProjectionStatus(string $name): ProjectionStatus
    {
        $this->ensureEventStoreIsPrepared();
        ;

        return $this->getProjectionManager()->fetchProjectionStatus($name);
    }

    public function fetchProjectionStreamPositions(string $name): array
    {
        $this->ensureEventStoreIsPrepared();
        ;

        return $this->getProjectionManager()->fetchProjectionStreamPositions($name);
    }

    public function fetchProjectionState(string $name): array
    {
        $this->ensureEventStoreIsPrepared();
        ;

        return $this->getProjectionManager()->fetchProjectionState($name);
    }

    public function getLazyProophEventStore(): LazyProophEventStore
    {
        return new LazyProophEventStore($this->eventSourcingConfiguration, $this->referenceSearchService);
    }

    private function triggerActionOnProjection(string $name): void
    {
        /** @var MessagingEntrypoint $messagingEntrypoint */
        $messagingEntrypoint = $this->referenceSearchService->get(MessagingEntrypoint::class);
        $messagingEntrypoint->send([], $this->projectionSetupConfigurations[$name]->getTriggeringChannelName());
    }

    public static function getProjectionStreamName(string $name): string
    {
        return 'projection_' . $name;
    }
}
