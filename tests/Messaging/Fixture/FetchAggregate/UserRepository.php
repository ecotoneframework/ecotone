<?php

namespace Test\Ecotone\Messaging\Fixture\FetchAggregate;

use Ecotone\Api\Attribute\Repository;
use Ecotone\Api\Modelling\StateStoredRepository;

#[Repository]
/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 */
class UserRepository implements StateStoredRepository
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
