<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting;

use RuntimeException;

final class ProjectionStateStorageRegistry
{
    /**
     * @param ProjectionStateStorage[] $storages
     */
    public function __construct(
        private array $userlandStorages,
        private array $builtinStorages,
    ) {
    }

    public function getFor(string $projectionName): ProjectionStateStorage
    {
        foreach ($this->userlandStorages as $storage) {
            if ($storage->canHandle($projectionName)) {
                return $storage;
            }
        }

        foreach ($this->builtinStorages as $storage) {
            if ($storage->canHandle($projectionName)) {
                return $storage;
            }
        }

        throw new RuntimeException("No projection state storage found for projection: {$projectionName}");
    }
}
