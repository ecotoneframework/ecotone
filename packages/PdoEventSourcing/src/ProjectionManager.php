<?php

namespace Ecotone\EventSourcing;

interface ProjectionManager
{
    /**
     * @param ProjectionExecutor $projectionExecutor to be called with
     * @param string[] $relatedEventClassNames events that projection is interested in. May be used for filtering the stream.
     */
    public function run(string $projectionName, ProjectionStreamSource $projectionStreamSource, ProjectionExecutor $projectionExecutor, array $relatedEventClassNames, array $projectionConfiguration): void;

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
