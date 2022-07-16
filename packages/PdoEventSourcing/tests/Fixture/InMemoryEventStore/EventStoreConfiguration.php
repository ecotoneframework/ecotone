<?php

namespace Test\Ecotone\EventSourcing\Fixture\InMemoryEventStore;

use Ecotone\Messaging\Attribute\ServiceContext;

class EventStoreConfiguration
{
    #[ServiceContext]
    public function configureProjection()
    {
        return \Ecotone\EventSourcing\EventSourcingConfiguration::createInMemory();
    }
}
