<?php

namespace Test\Ecotone\EventSourcing\Fixture\InMemoryEventStore;

use Ecotone\EventSourcing\ProjectionRunningConfiguration;
use Ecotone\Messaging\Attribute\ServiceContext;
use Ecotone\Messaging\Endpoint\PollingMetadata;

class EventStoreConfiguration
{
    #[ServiceContext]
    public function configureProjection()
    {
        return \Ecotone\EventSourcing\EventSourcingConfiguration::createInMemory();
    }
}