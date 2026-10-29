<?php

namespace Test\Ecotone\Messaging\Fixture\FetchAggregate;

use Ecotone\Modelling\Attribute\Repository;
use Ecotone\Modelling\StandardRepository;

#[Repository]
/**
 * licence Enterprise
 */
class UserRepository implements StandardRepository
{
    public function __construct(
        private array $users = []
    ) {
        foreach ($users as $user) {
            $this->addUser($user);
        }
    }

    public function canHandle(string $aggregateClassName): bool
    {
        return $aggregateClassName === User::class;
    }

    public function findBy(string $aggregateClassName, array $identifiers): ?object
    {
        if (! array_key_exists('userId', $identifiers)) {
            return null;
        }

        $userId = array_pop($identifiers);
        return $this->users[$userId] ?? null;
    }

    public function save(array $identifiers, object $aggregate, array $metadata, ?int $versionBeforeHandling): void
    {
        if ($aggregate instanceof User) {
            $this->users[$aggregate->getUserId()] = $aggregate;
        }
    }

    public function addUser(User $user): void
    {
        $this->users[$user->getUserId()] = $user;
    }
}
