<?php

namespace Test\Ecotone\EventSourcing\Fixture\SpecificEventStream;

use Ecotone\Messaging\Attribute\ServiceContext;

class EventSourcingConfiguration
{
    #[ServiceContext]
    public function aggregateStreamStrategy()
    {
        return \Ecotone\EventSourcing\EventSourcingConfiguration::createWithDefaults()
            ->withStreamPerAggregatePersistenceStrategy();
    }
}
