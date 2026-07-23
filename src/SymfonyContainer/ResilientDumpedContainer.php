<?php

declare(strict_types=1);

namespace Ecotone\SymfonyContainer;

use Symfony\Component\DependencyInjection\Container;

/**
 * Base class for the dumped Ecotone container (PhpDumper's base_class), so a
 * resolution failure never leaves half-built services behind.
 *
 * licence Apache-2.0
 */
class ResilientDumpedContainer extends Container
{
    use DiscardsHalfBuiltServices;
}
