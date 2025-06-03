<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Modelling\Repository;

use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\ResolvedAggregate;

class AllAggregateRepository implements AggregateRepository
{
    /**
     * @param AggregateRepository[] $aggregateRepositories
     */
    public function __construct(private array $aggregateRepositories)
    {
    }

    public function canHandle(string $aggregateClassName): bool
    {
        foreach ($this->aggregateRepositories as $aggregateRepository) {
            if ($aggregateRepository->canHandle($aggregateClassName)) {
                return true;
            }
        }

        return false;
    }

    public function findBy(string $aggregateClassName, array $identifiers): ?ResolvedAggregate
    {
        foreach ($this->aggregateRepositories as $aggregateRepository) {
            if ($aggregateRepository->canHandle($aggregateClassName)) {
                return $aggregateRepository->findBy($aggregateClassName, $identifiers);
            }
        }

        return null;
    }

    public function save(ResolvedAggregate $aggregate, array $metadata): int
    {
        foreach ($this->aggregateRepositories as $aggregateRepository) {
            if ($aggregateRepository->canHandle($aggregate->getAggregateClassName())) {
                return $aggregateRepository->save($aggregate, $metadata);
            }
        }
        throw InvalidArgumentException::create('There is no repository available for aggregate: ' . $aggregate->getAggregateClassName() . '. This happens because are multiple Repositories of given type registered, therefore each Repository need to specify which aggregate it can handle. If this fails during Ecotone Lite tests, consider turning off default In Memory implementations.');
    }
}
