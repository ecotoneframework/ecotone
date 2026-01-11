<?php

/*
 * licence Enterprise
 */

declare(strict_types=1);

namespace Ecotone\Projecting;

/**
 * Reference to a PartitionProvider service registered in the container.
 * Event sourcing modules register their PartitionProvider implementations as services
 * and provide this reference so PartitionProviderRegistryModule can collect them.
 */
final class PartitionProviderReference
{
    /**
     * @param string $referenceName
     * @param string[] $partitionedProjectionNames
     */
    public function __construct(
        private string $referenceName,
        private array $partitionedProjectionNames,
    ) {
    }

    public function getReferenceName(): string
    {
        return $this->referenceName;
    }

    /**
     * @return string[]
     */
    public function getPartitionedProjectionNames(): array
    {
        return $this->partitionedProjectionNames;
    }
}
