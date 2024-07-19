<?php

namespace Test\Ecotone\Modelling\Fixture\EventSourcingRepositoryShortcut;

/**
 * licence Apache-2.0
 */
class TwitContentWasChanged
{
    public function __construct(public string $twitId, public string $content)
    {
    }
}
