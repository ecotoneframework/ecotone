<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting;

use Psr\Container\ContainerInterface;

interface ProjectionRegistry extends ContainerInterface
{
    public function get(string $id): ProjectingManager;
    /** @return iterable<string> */
    public function projectionNames(): iterable;
}
