<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Modelling\Repository;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;

interface AggregateRepositoryBuilder
{
    public function canHandle(string $repositoryClassName): bool;

    public function compile(string $referenceId, bool $isDefault): Definition|Reference;
}
