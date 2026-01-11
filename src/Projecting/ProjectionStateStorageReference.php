<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting;

/**
 * Reference to a ProjectionStateStorage service registered in the container.
 * Event sourcing modules register their ProjectionStateStorage implementations as services
 * and provide this reference so ProjectionStateStorageRegistryModule can collect them.
 */
class ProjectionStateStorageReference
{
    /**
     * @param string $referenceName
     */
    public function __construct(
        private string $referenceName,
    ) {
    }

    public function getReferenceName(): string
    {
        return $this->referenceName;
    }
}
