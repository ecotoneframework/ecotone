<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\CustomRepositories\Standard;

use Ecotone\Api\Attribute\Repository;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryStateStoredRepository;

#[Repository]
/**
 * licence Apache-2.0
 */
final class ArticleRepository extends InMemoryStateStoredRepository
{
    public function canHandle(string $aggregateClassName): bool
    {
        return $aggregateClassName === Article::class;
    }
}
