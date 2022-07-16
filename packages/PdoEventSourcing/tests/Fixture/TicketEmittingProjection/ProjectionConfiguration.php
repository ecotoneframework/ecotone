<?php

namespace Test\Ecotone\EventSourcing\Fixture\TicketEmittingProjection;

use Ecotone\EventSourcing\ProjectionRunningConfiguration;
use Ecotone\Messaging\Attribute\ServiceContext;

class ProjectionConfiguration
{
    #[ServiceContext]
    public function configureProjection()
    {
        return [
            ProjectionRunningConfiguration::createEventDriven(InProgressTicketList::NAME)
                ->withTestingSetup(),
        ];
    }
}
