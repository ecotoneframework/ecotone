<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Modelling\Repository;

use Ecotone\Api\Modelling\StateStoredRepository;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateDefinitionRegistry;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\ResolvedAggregate;

class StateStoredRepositoryAdapter implements AggregateRepository
{
    public function __construct(
        private StateStoredRepository $standardRepository,
        private AggregateDefinitionRegistry $aggregateDefinitionRegistry,
        private bool $isDefaultRepository,
        private AggregateCounter $aggregateCounter,
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
        $counterCapturedAtLoad = $this->aggregateCounter->captureFor($aggregateClassName, $identifiers);
        $aggregate = $this->standardRepository->findBy($aggregateClassName, $identifiers);

        if ($aggregate === null) {
            return null;
        }

        return (new ResolvedAggregate(
            $this->aggregateDefinitionRegistry->getFor($aggregateClassName),
            false,
            $aggregate,
            null,
            $identifiers,
            [],
        ))->withCounterCapturedAtLoad($counterCapturedAtLoad);
    }

    public function save(ResolvedAggregate $aggregate, array $metadata): ?int
    {
        $this->aggregateCounter->bumpGuarded($aggregate->getAggregateClassName(), $aggregate->getIdentifiers(), $aggregate->getCounterCapturedAtLoad());

        $this->standardRepository->save(
            $aggregate->getIdentifiers(),
            $aggregate->getAggregateInstance(),
            $metadata,
            $aggregate->getVersionBeforeHandling()
        );

        return $aggregate->getVersionBeforeHandling() === null ? null : $aggregate->getVersionBeforeHandling() + 1;
    }
}
