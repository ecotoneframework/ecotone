<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Modelling\Repository;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateDefinitionRegistry;
use Ecotone\Modelling\StandardRepository;

use function is_a;

class StandardRepositoryAdapterBuilder implements AggregateRepositoryBuilder
{
    public function canHandle(string $repositoryClassName): bool
    {
        return is_a($repositoryClassName, StandardRepository::class, true);
    }

    public function compile(string $referenceId, bool $isDefault): Definition|Reference
    {
        return new Definition(StandardRepositoryAdapter::class, [
            new Reference($referenceId),
            new Reference(AggregateDefinitionRegistry::class),
            $isDefault,
        ]);
    }
}
