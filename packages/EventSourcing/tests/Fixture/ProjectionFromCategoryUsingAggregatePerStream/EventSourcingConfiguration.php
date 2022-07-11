<?php

namespace Test\Ecotone\EventSourcing\Fixture\ProjectionFromCategoryUsingAggregatePerStream;

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
