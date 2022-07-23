<?php

namespace Ecotone\EventSourcing\Prooph;

use Ecotone\EventSourcing\EventSourcingConfiguration;
use Ecotone\EventSourcing\ProjectionExecutor;
use Ecotone\EventSourcing\ProjectionSetupConfiguration;
use Ecotone\EventSourcing\ProjectionStreamSource;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Modelling\Event;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Exception\RuntimeException;
use Prooph\EventStore\Pdo\Projection\MariaDbProjectionManager;
use Prooph\EventStore\Pdo\Projection\MySqlProjectionManager;
use Prooph\EventStore\Pdo\Projection\PostgresProjectionManager;
use Prooph\EventStore\Projection\ProjectionManager;
use Prooph\EventStore\Projection\ProjectionStatus;
use Prooph\EventStore\Projection\Projector;
use Prooph\EventStore\Projection\Query;
use Prooph\EventStore\Projection\ReadModel;
use Prooph\EventStore\Projection\ReadModelProjector;

use function str_contains;

class LazyProophProjectionManager implements ProjectionManager
{
    private ?ProjectionManager $lazyInitializedProjectionManager = null;

    /**
     * @param ProjectionSetupConfiguration[] $projectionSetupConfigurations
     */
    public function __construct(
        private EventSourcingConfiguration $eventSourcingConfiguration,
        private array                      $projectionSetupConfigurations,
        private ReferenceSearchService     $referenceSearchService
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

    public function hasInitializedProjectionWithName(string $name): bool
    {
        $this->ensureEventStoreIsPrepared();

        return (bool)$this->getProjectionManager()->fetchProjectionNames($name, 1, 0);
    }

    public function getProjectionStatus(string $name): \Ecotone\EventSourcing\ProjectionStatus
    {
        $this->ensureEventStoreIsPrepared();

        return match ($this->getProjectionManager()->fetchProjectionStatus($name)->getValue()) {
            ProjectionStatus::DELETING, ProjectionStatus::DELETING_INCL_EMITTED_EVENTS => \Ecotone\EventSourcing\ProjectionStatus::DELETING(),
            ProjectionStatus::STOPPING, ProjectionStatus::IDLE, ProjectionStatus::RUNNING => \Ecotone\EventSourcing\ProjectionStatus::RUNNING(),
            ProjectionStatus::RESETTING => \Ecotone\EventSourcing\ProjectionStatus::REBUILDING()
        };
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

    public function getProjectionState(string $name): array
    {
        return $this->getProjectionManager()->fetchProjectionState($name);
    }

    public function fetchProjectionState(string $name): array
    {
        $this->ensureEventStoreIsPrepared();
        ;

        return $this->getProjectionManager()->fetchProjectionState($name);
    }

    public function run(string $projectionName, ProjectionStreamSource $projectionStreamSource, ProjectionExecutor $projectionExecutor, array $relatedEventClassNames, array $projectionConfiguration): void
    {
        $handlers = [];
        foreach ($relatedEventClassNames as $eventName) {
            $handlers[$eventName] = function ($state, Message $event) use ($eventName, $projectionExecutor): mixed {
                return $projectionExecutor->executeWith(
                    $eventName,
                    Event::createWithType($eventName, $event->payload(), $event->metadata()),
                    $state
                );
            };
        }

        $projection = $this->createReadModelProjection($projectionName, new ProophReadModel(), $projectionConfiguration);
        if ($projectionStreamSource->isForAllStreams()) {
            $projection = $projection->fromAll();
        } elseif ($projectionStreamSource->getCategories()) {
            $projection = $projection->fromCategories(...$projectionStreamSource->getCategories());
        } elseif ($projectionStreamSource->getStreams()) {
            $projection = $projection->fromStreams(...$projectionStreamSource->getStreams());
        }
        $projection = $projection->when($handlers);

        try {
            $projection->run(false);
        } catch (RuntimeException $exception) {
            if (! str_contains($exception->getMessage(), 'Another projection process is already running')) {
                throw $exception;
            }

            sleep(1);
            $projection->run(false);
        }
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
