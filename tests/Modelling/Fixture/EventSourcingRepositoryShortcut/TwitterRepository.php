<?php

namespace Test\Ecotone\Modelling\Fixture\EventSourcingRepositoryShortcut;

use Ecotone\Api\Attribute\RelatedAggregate;
use Ecotone\Api\Attribute\Repository;

/**
 * licence Apache-2.0
 */
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
