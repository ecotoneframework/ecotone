<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Modelling\Repository;

use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\ResolvedAggregate;

interface AggregateRepository
{
    public function canHandle(string $aggregateClassName): bool;

    public function findBy(string $aggregateClassName, array $identifiers): ?ResolvedAggregate;

    public function save(ResolvedAggregate $aggregate, array $metadata): void;
}
