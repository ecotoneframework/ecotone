<?php

declare(strict_types=1);

namespace Ecotone\SymfonyContainer;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * ContainerBuilder used when Ecotone runs without a dumped container (no
 * cache), so a resolution failure never leaves half-built services behind.
 *
 * licence Apache-2.0
 */
class ResilientContainerBuilder extends ContainerBuilder
{
    use DiscardsHalfBuiltServices;
}
