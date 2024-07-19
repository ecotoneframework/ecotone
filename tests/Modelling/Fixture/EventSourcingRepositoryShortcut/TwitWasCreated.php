<?php

namespace Test\Ecotone\Modelling\Fixture\EventSourcingRepositoryShortcut;

/**
 * licence Apache-2.0
 */
class TwitWasCreated
{
    public function __construct(public string $twitId, public string $content)
    {
    }
}
