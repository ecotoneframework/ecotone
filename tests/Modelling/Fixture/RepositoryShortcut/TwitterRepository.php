<?php

namespace Ecotone\Tests\Modelling\Fixture\RepositoryShortcut;

use Ecotone\Modelling\Attribute\Repository;

interface TwitterRepository
{
    #[Repository]
    public function getTwitter(string $twitId): Twitter;

    #[Repository]
    public function findTwitter(string $twitId): ?Twitter;

    #[Repository]
    public function save(Twitter $twitter): void;
}