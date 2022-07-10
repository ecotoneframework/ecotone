<?php

namespace Test\Ecotone\Modelling\Fixture\EventSourcingRepositoryShortcut;

use Ecotone\Modelling\Attribute\RelatedAggregate;
use Ecotone\Modelling\Attribute\Repository;

interface TwitterRepository
{
    #[Repository]
    public function getTwitter(string $twitId): Twitter;

    #[Repository]
    public function findTwitter(string $twitId): ?Twitter;

    #[Repository]
    #[RelatedAggregate(Twitter::class)]
    public function save(string $aggregateId, int $currentVersion, array $events): void;
}