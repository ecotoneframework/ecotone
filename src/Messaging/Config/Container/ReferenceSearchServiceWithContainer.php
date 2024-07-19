<?php

namespace Ecotone\Messaging\Config\Container;

use Ecotone\Messaging\Handler\ReferenceSearchService;
use Psr\Container\ContainerInterface;

/**
 * licence Apache-2.0
 */
class ReferenceSearchServiceWithContainer implements ReferenceSearchService
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function get(string $referenceName): object
    {
        return $this->container->get($referenceName);
    }

    public function has(string $referenceName): bool
    {
        return $this->container->has($referenceName);
    }
}
