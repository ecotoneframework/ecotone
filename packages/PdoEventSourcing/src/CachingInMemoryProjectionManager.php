<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing;

use Prooph\EventStore\Projection\InMemoryProjectionManager;
use Prooph\EventStore\Projection\ProjectionStatus;
use Prooph\EventStore\Projection\Projector;
use Prooph\EventStore\Projection\Query;
use Prooph\EventStore\Projection\ReadModel;
use Prooph\EventStore\Projection\ReadModelProjector;

class CachingInMemoryProjectionManager implements \Prooph\EventStore\Projection\ProjectionManager
{
    /**
     * @var ReadModelProjector[]
     */
    private array $readModelProjections = [];

    public function __construct(private InMemoryProjectionManager $inMemoryProjectionManager)
    {
    }

    public function createQuery(): Query
    {
        return $this->inMemoryProjectionManager->createQuery();
    }

    public function createProjection(string $name, array $options = []): Projector
    {
        $options['sleep'] = 1;

        return $this->inMemoryProjectionManager->createProjection($name, $options);
    }

    public function createReadModelProjection(string $name, ReadModel $readModel, array $options = []): ReadModelProjector
    {
        $options['sleep'] = 1;

        if (isset($this->readModelProjections[$name])) {
            return $this->readModelProjections[$name];
        }

        $readModelProjector = $this->inMemoryProjectionManager->createReadModelProjection($name, $readModel, $options);
        $this->readModelProjections[$name] = new CachingInMemoryReadModelProjector($readModelProjector);

        return $this->readModelProjections[$name];
    }

    public function deleteProjection(string $name, bool $deleteEmittedEvents): void
    {
        $this->inMemoryProjectionManager->deleteProjection($name, $deleteEmittedEvents);
    }

    public function resetProjection(string $name): void
    {
        $this->inMemoryProjectionManager->resetProjection($name);
    }

    public function stopProjection(string $name): void
    {
        $this->inMemoryProjectionManager->stopProjection($name);
    }

    public function fetchProjectionNames(?string $filter, int $limit = 20, int $offset = 0): array
    {
        return $this->inMemoryProjectionManager->fetchProjectionNames($filter, $limit, $offset);
    }

    public function fetchProjectionNamesRegex(string $regex, int $limit = 20, int $offset = 0): array
    {
        return $this->inMemoryProjectionManager->fetchProjectionNamesRegex($regex, $limit, $offset);
    }

    public function fetchProjectionStatus(string $name): ProjectionStatus
    {
        return $this->inMemoryProjectionManager->fetchProjectionStatus($name);
    }

    public function fetchProjectionStreamPositions(string $name): array
    {
        return $this->inMemoryProjectionManager->fetchProjectionStreamPositions($name);
    }

    public function fetchProjectionState(string $name): array
    {
        return $this->inMemoryProjectionManager->fetchProjectionState($name);
    }
}
