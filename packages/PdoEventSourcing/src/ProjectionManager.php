<?php

namespace Ecotone\EventSourcing;

use Prooph\EventStore\Exception\ProjectionNotFound;
use Prooph\EventStore\Projection\ReadModel;
use Prooph\EventStore\Projection\ReadModelProjector;

interface ProjectionManager
{
    public function createReadModelProjection(
        string    $name,
        ReadModel $readModel,
        array     $options = []
    ): ReadModelProjector;

    /**
     * @param string $name
     * @param array<class-string, callable> $handlers callable should be called with (array payload, array metadata = [], array projectionState = []): ?array (state)
     * @param ProjectionSource $projectionSource
     * @return void
     */
    public function run(string $name, array $handlers, ProjectionSource $projectionSource): void;

    /**
     * @throws ProjectionNotFoundException
     */
    public function deleteProjection(string $name): void;

    /**
     * @throws ProjectionNotFoundException
     */
    public function resetProjection(string $name): void;

    /**
     * @throws ProjectionNotFoundException
     */
    public function stopProjection(string $name): void;

    /**
     * @throws ProjectionNotFoundException
     */
    public function initializeProjection(string $name): void;

    public function hasInitializedProjectionWithName(string $name): bool;

    /**
     * @throws ProjectionNotFoundException
     */
    public function getProjectionStatus(string $name): ProjectionStatus;

    /**
     * @throws ProjectionNotFoundException
     */
    public function getProjectionState(string $name): array;
}
