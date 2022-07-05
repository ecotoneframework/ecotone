<?php

namespace Ecotone\Tests\Modelling\Fixture\EventSourcingRepositoryShortcut;

class TwitWasCreated
{
    public function __construct(public string $twitId, public string $content)
    {
    }
}