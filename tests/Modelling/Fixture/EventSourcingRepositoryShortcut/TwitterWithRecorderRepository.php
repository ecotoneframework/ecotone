<?php

namespace Test\Ecotone\Modelling\Fixture\EventSourcingRepositoryShortcut;

use Ecotone\Modelling\Attribute\Repository;

/**
 * licence Apache-2.0
 */
interface TwitterWithRecorderRepository
{
    #[Repository]
    public function getTwitter(string $twitId): TwitterWithRecorder;

    #[Repository]
    public function findTwitter(string $twitId): ?TwitterWithRecorder;

    #[Repository]
    public function save(TwitterWithRecorder $aggregate): void;
}
