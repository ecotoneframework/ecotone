<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Config\Container\CompilableBuilder;

interface RepositoryBuilder extends CompilableBuilder
{
    public function canHandle(string $aggregateClassName): bool;

    public function isEventSourced(): bool;
}
