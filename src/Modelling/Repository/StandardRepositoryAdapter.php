<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Modelling\Repository;

use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateDefinitionRegistry;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\ResolvedAggregate;
use Ecotone\Modelling\StandardRepository;

class StandardRepositoryAdapter implements AggregateRepository
{
    public function __construct(
        private StandardRepository $standardRepository,
        private AggregateDefinitionRegistry $aggregateDefinitionRegistry,
        private bool $isDefaultRepository,
    ) {
    }

    public function canHandle(string $aggregateClassName): bool
    {
        if ($this->isDefaultRepository && $this->aggregateDefinitionRegistry->has($aggregateClassName)) {
            $aggregateDefinition = $this->aggregateDefinitionRegistry->getFor($aggregateClassName);
            return ! $aggregateDefinition->isEventSourced();
        }
        return $this->standardRepository->canHandle($aggregateClassName);
    }

    public function findBy(string $aggregateClassName, array $identifiers): ?ResolvedAggregate
    {
        $aggregate = $this->standardRepository->findBy($aggregateClassName, $identifiers);

        if ($aggregate === null) {
            return null;
        }

        return new ResolvedAggregate(
            $this->aggregateDefinitionRegistry->getFor($aggregateClassName),
            false,
            $aggregate,
            null,
            $identifiers,
            [],
        );
    }

    public function save(ResolvedAggregate $aggregate, array $metadata): int
    {
        $this->standardRepository->save(
            $aggregate->getIdentifiers(),
            $aggregate->getAggregateInstance(),
            $metadata,
            $aggregate->getVersionBeforeHandling()
        );

        return $aggregate->getVersionBeforeHandling() ? $aggregate->getVersionBeforeHandling() + 1 : 0;
    }
}
