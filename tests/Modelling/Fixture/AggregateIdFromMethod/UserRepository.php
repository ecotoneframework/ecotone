<?php

namespace Test\Ecotone\Modelling\Fixture\AggregateIdFromMethod;

use Ecotone\Api\Attribute\Repository;
use Ecotone\Api\Modelling\StateStoredRepository;

#[Repository]
/**
 * licence Apache-2.0
 */
class UserRepository implements StateStoredRepository
{
    private array $users;

    public function canHandle(string $aggregateClassName): bool
    {
        return User::class;
    }

    public function findBy(string $aggregateClassName, array $identifiers): ?object
    {
        return $this->users[array_pop($identifiers)];
    }

    public function save(array $identifiers, object $aggregate, array $metadata, ?int $versionBeforeHandling): void
    {
        $this->users[array_pop($identifiers)] = $aggregate;
    }
}
