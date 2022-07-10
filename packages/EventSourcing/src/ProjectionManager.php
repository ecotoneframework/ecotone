<?php


namespace Ecotone\EventSourcing;

use Prooph\EventStore\Exception\ProjectionNotFound;
use Prooph\EventStore\Projection\ProjectionStatus;
use Prooph\EventStore\Projection\Projector;
use Prooph\EventStore\Projection\Query;
use Prooph\EventStore\Projection\ReadModel;
use Prooph\EventStore\Projection\ReadModelProjector;

interface ProjectionManager
{
    public function createQuery(): Query;

    public function createProjection(
        string $name,
        array $options = []
    ): Projector;

    public function createReadModelProjection(
        string $name,
        ReadModel $readModel,
        array $options = []
    ): ReadModelProjector;

    /**
     * @throws ProjectionNotFound
     */
    public function deleteProjection(string $name, bool $deleteEmittedEvents = true): void;

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
     * @return string[]
     */
    public function fetchProjectionNamesRegex(string $regex, int $limit = 20, int $offset = 0): array;

    /**
     * @throws ProjectionNotFound
     */
    public function fetchProjectionStatus(string $name): ProjectionStatus;

    /**
     * @throws ProjectionNotFound
     */
    public function fetchProjectionStreamPositions(string $name): array;

    /**
     * @throws ProjectionNotFound
     */
    public function fetchProjectionState(string $name): array;
}