<?php

namespace Ecotone\EventSourcing\Prooph;

use Ecotone\EventSourcing\ProjectionManager;
use Ecotone\EventSourcing\ProjectionNotFoundException;
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

    public function hasInitializedProjectionWithName(string $name): bool
    {
        // TODO: Implement hasInitializedProjectionWithName() method.
    }

    public function getProjectionStatus(string $name): \Ecotone\EventSourcing\ProjectionStatus
    {
        // TODO: Implement getProjectionStatus() method.
    }

    public function getProjectionState(string $name): array
    {
        // TODO: Implement getProjectionState() method.
    }

    public function deleteProjection(string $name, bool $deleteEmittedEvents = true): void
    {
        // TODO: Implement deleteProjection() method.
    }
}