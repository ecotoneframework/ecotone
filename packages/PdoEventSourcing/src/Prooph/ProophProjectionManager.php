<?php

namespace Ecotone\EventSourcing\Prooph;

use Ecotone\EventSourcing\ProjectionManager;
use Ecotone\EventSourcing\ProjectionSource;
use Prooph\EventStore\Exception\ProjectionNotFound;
use Prooph\EventStore\Projection\ProjectionStatus;
use Prooph\EventStore\Projection\ReadModel;
use Prooph\EventStore\Projection\ReadModelProjector;

final class ProophProjectionManager implements ProjectionManager
{
    public function createReadModelProjection(string $name, ReadModel $readModel, array $options = []): ReadModelProjector
    {
        // TODO: Implement createReadModelProjection() method.
    }

    public function run(string $name, array $handlers, ProjectionSource $projectionSource): void
    {
        // TODO: Implement run() method.
    }

    public function resetProjection(string $name): void
    {
        // TODO: Implement resetProjection() method.
    }

    public function stopProjection(string $name): void
    {
        // TODO: Implement stopProjection() method.
    }

    public function initializeProjection(string $name): void
    {
        // TODO: Implement initializeProjection() method.
    }

    public function fetchProjectionNames(?string $filter, int $limit = 20, int $offset = 0): array
    {
        // TODO: Implement fetchProjectionNames() method.
    }

    public function fetchProjectionStatus(string $name): ProjectionStatus
    {
        // TODO: Implement fetchProjectionStatus() method.
    }

    public function fetchProjectionState(string $name): array
    {
        // TODO: Implement fetchProjectionState() method.
    }

    public function deleteProjection(string $name, bool $deleteEmittedEvents = true): void
    {
        // TODO: Implement deleteProjection() method.
    }
}