<?php

namespace Test\Ecotone\EventSourcing\Fixture\TicketWithLimitedLoad;

use Ecotone\EventSourcing\EventSourcingConfiguration;
use Ecotone\Messaging\Attribute\ServiceContext;

class ProjectionConfiguration
{
    #[ServiceContext]
    public function configureProjection()
    {
        return [
            EventSourcingConfiguration::createWithDefaults()
                ->withLoadBatchSize(1)
        ];
    }
}