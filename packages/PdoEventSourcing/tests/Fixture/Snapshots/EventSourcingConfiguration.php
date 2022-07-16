<?php

namespace Test\Ecotone\EventSourcing\Fixture\Snapshots;

use Ecotone\Messaging\Attribute\ServiceContext;
use Test\Ecotone\EventSourcing\Fixture\Basket\Basket;
use Test\Ecotone\EventSourcing\Fixture\Ticket\Ticket;

class EventSourcingConfiguration
{
    #[ServiceContext]
    public function aggregateSnapshots()
    {
        return \Ecotone\EventSourcing\EventSourcingConfiguration::createWithDefaults()
            ->withSnapshots([Ticket::class, Basket::class], 1);
    }
}
