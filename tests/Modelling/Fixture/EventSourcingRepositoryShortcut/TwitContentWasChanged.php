<?php

namespace Tests\Ecotone\Modelling\Fixture\EventSourcingRepositoryShortcut;

class TwitContentWasChanged
{
    public function __construct(public string $twitId, public string $content)
    {
    }
}