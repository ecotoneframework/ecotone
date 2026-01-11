<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting;

/**
 * Reference to a StreamSource service registered in the container.
 * Event sourcing modules register their StreamSource implementations as services
 * and provide this reference so StreamSourceRegistryModule can collect them.
 */
final class StreamSourceReference
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
