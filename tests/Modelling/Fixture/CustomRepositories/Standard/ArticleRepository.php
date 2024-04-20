<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\CustomRepositories\Standard;

use Ecotone\Modelling\Attribute\Repository;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryStandardRepository;

#[Repository]
final class ArticleRepository extends InMemoryStandardRepository
{
    public function canHandle(string $aggregateClassName): bool
    {
        return $aggregateClassName === Article::class;
    }
}
