<?php

namespace Test\Ecotone\Modelling\Fixture\LateAggregateIdAssignation;

use Ecotone\Modelling\Attribute\Repository;
use Ecotone\Modelling\StandardRepository;
use Symfony\Component\Uid\Uuid;

#[Repository]
/**
 * licence Apache-2.0
 */
class UserRepository implements StandardRepository
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
        $aggregate->id = Uuid::v7()->toRfc4122();
        $this->users[$aggregate->id] = $aggregate;
    }
}
