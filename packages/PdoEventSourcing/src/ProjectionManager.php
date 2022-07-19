<?php

namespace Ecotone\EventSourcing;

use Prooph\EventStore\Exception\ProjectionNotFound;
use Prooph\EventStore\Projection\ProjectionStatus;
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
     * @throws ProjectionNotFound
     */
    public function deleteProjection(string $name): void;

    /**
     * @throws ProjectionNotFound
     */
    public function resetProjection(string $name): void;

    /**
     * @throws ProjectionNotFound
     */
    public function stopProjection(string $name): void;

    /**
     * @throws ProjectionNotFound
     */
    public function initializeProjection(string $name): void;

    /**
     * @return string[]
     */
    public function fetchProjectionNames(?string $filter, int $limit = 20, int $offset = 0): array;

    /**
     * @throws ProjectionNotFound
     */
    public function fetchProjectionStatus(string $name): ProjectionStatus;

    /**
     * @throws ProjectionNotFound
     */
    public function fetchProjectionState(string $name): array;
}
