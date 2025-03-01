<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Config\Container\CompilableBuilder;

/**
 * licence Apache-2.0
 */
interface RepositoryBuilder extends CompilableBuilder
{
    public function canHandle(string $aggregateClassName): bool;
}
